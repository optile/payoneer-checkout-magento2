<?php

namespace Payoneer\OpenPaymentGateway\Cron;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification\CollectionFactory;
use Payoneer\OpenPaymentGateway\Model\TransactionOrderUpdater;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification\Collection;
use Payoneer\OpenPaymentGateway\Logger\NotificationLogger;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Helper;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Update order on running cron with notification data
 */
class OrderUpdate
{
    /**
     * @var CollectionFactory
     */
    private $notificationCollectionFactory;

    /**
     * @var TransactionOrderUpdater
     */
    protected $transactionOrderUpdater;

    /**
     * @var NotificationLogger
     */
    protected $notificationLogger;
    
    /**
     * @var Config
     */
    protected $config;
    
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * OrderUpdate construct function
     *
     * @param CollectionFactory $notificationCollectionFactory
     * @param TransactionOrderUpdater $transactionOrderUpdater
     * @param NotificationLogger $notificationLogger
     * @param Config $config
     * @param TimezoneInterface $timezone
     * @return void
     */
    public function __construct(
        CollectionFactory $notificationCollectionFactory,
        TransactionOrderUpdater $transactionOrderUpdater,
        NotificationLogger $notificationLogger,
        Config $config,
        TimezoneInterface $timezone
    ) {
        $this->notificationCollectionFactory = $notificationCollectionFactory;
        $this->transactionOrderUpdater = $transactionOrderUpdater;
        $this->notificationLogger = $notificationLogger;
        $this->config = $config;
        $this->timezone = $timezone;
    }

    /**
     * Get the non-processed notification and process it.
     *
     * @return bool|void
     */
    public function execute()
    {
        try {
            $notificationCollection = $this->getNotificationsToCleanUp();
            if ($notificationCollection != null) {
                $notificationCollection->walk('delete');
            }
        } catch (\Exception $e) {
            $this->notificationLogger->addError(
                __('NotificationCleanupError: %1', $e->getMessage())
            );
        }

        $notifications = $this->getNotifications();
        if ($notifications->getSize() > 0) {
            foreach ($notifications as $notification) {
                try {
                    $response = \Safe\json_decode($notification->getContent(), true);
                    if (!isset($response['statusCode'])) {
                        continue;
                    }
                    if (isset($response['interactionReason']) &&
                        $response['interactionReason'] == Helper::SYSTEM_FAILURE
                    ) {
                        continue;
                    }
                    $this->transactionOrderUpdater->processNotificationResponse(
                        $notification->getOrderId(),
                        $response
                    );
                } catch (\Exception $e) {
                    $this->notificationLogger->addError(
                        __('CronProcess: #id=%1, Error = %2', $notification->getId(), $e->getMessage())
                    );
                    continue;
                }
                try {
                    $notification->setCronStatus(1);
                    $notification->save();
                } catch (\Exception $e) {
                    $this->notificationLogger->addError(
                        __('CronNotificationnSave: #id=%1, Error = %2', $notification->getId(), $e->getMessage())
                    );
                    continue;
                }
            }
        }
        return true;
    }
    
    /**
     * Get all the notifications to cleanup
     *
     * @return Collection|null
     */
    private function getNotificationsToCleanUp()
    {
        $cleanupDays = $this->config->getNotificationCleanupDays();
        if (empty($cleanupDays)) {
            return null;
        }        
        $collection = $this->notificationCollectionFactory->create();
        $collection->addFieldToFilter('cron_status', ['eq' => 1]);
        $collection->addFieldToFilter(
            'created_at', 
            ['lteq' => $this->getUtcDateXDaysBefore($cleanupDays)]
        );        

        return $collection;
    }
    
    /**
     * Get all the notifications to which the email should be send
     *
     * @return Collection|null
     */
    private function getNotificationsToSendEmail()
    {
        $emailSendDays = $this->config->getNotificationEmailSendingDays();
        if (empty($emailSendDays)) {
            return null;
        }        
        $collection = $this->notificationCollectionFactory->create();
        $collection->addFieldToFilter('cron_status', ['eq' => 0]);
        $collection->addFieldToFilter(
            'created_at', 
            ['lteq' => $this->getUtcDateXDaysBefore($emailSendDays)]
        );        

        return $collection;
    }

    private function getUtcDateXDaysBefore($noOfDays)
    {
        return $this->timezone->date()->setTimezone(new \DateTimeZone('UTC'))
                    ->modify('-'.$noOfDays.' day')
                    ->format('Y-m-d 23:59:59');
    }
}
