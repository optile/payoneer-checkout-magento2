<?php

namespace Payoneer\OpenPaymentGateway\Cron;

use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification\CollectionFactory;
use Payoneer\OpenPaymentGateway\Model\TransactionOrderUpdater;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification\Collection;
use Payoneer\OpenPaymentGateway\Logger\NotificationLogger;

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
     * OrderUpdate construct function
     *
     * @param CollectionFactory $notificationCollectionFactory
     * @param TransactionOrderUpdater $transactionOrderUpdater
     * @param NotificationLogger $notificationLogger
     * @return void
     */
    public function __construct(
        CollectionFactory $notificationCollectionFactory,
        TransactionOrderUpdater $transactionOrderUpdater,
        NotificationLogger $notificationLogger
    ) {
        $this->notificationCollectionFactory = $notificationCollectionFactory;
        $this->transactionOrderUpdater = $transactionOrderUpdater;
        $this->notificationLogger = $notificationLogger;
    }

    /**
     * Get the non-processed notification and process it.
     *
     * @return bool|void
     */
    public function execute()
    {
        $notifications = $this->getNotifications();
        if ($notifications->getSize() > 0) {
            foreach ($notifications as $notification) {
                try {
                    $response = \Safe\json_decode($notification->getContent(), true);
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
     * Get the non-processed notifications.
     *
     * @return Collection
     */
    private function getNotifications()
    {
        $collection = $this->notificationCollectionFactory->create();
        $collection->addFieldToFilter('cron_status', ['eq' => 0]);
        $collection->setOrder('created_at', 'ASC');

        return $collection;
    }
}
