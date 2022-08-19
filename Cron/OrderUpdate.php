<?php

namespace Payoneer\OpenPaymentGateway\Cron;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification\CollectionFactory;
use Payoneer\OpenPaymentGateway\Model\TransactionOrderUpdater;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification\Collection;
use Payoneer\OpenPaymentGateway\Logger\NotificationLogger;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\NotificationEmailSender;
use Payoneer\OpenPaymentGateway\Api\PayoneerNotificationRepositoryInterface;

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
     * @var NotificationEmailSender
     */
    protected $emailSender;

    /**
     * @var PayoneerNotificationRepositoryInterface
     */
    protected $notificationRepository;

    /**
     * OrderUpdate construct function
     *
     * @param CollectionFactory $notificationCollectionFactory
     * @param TransactionOrderUpdater $transactionOrderUpdater
     * @param NotificationLogger $notificationLogger
     * @param Config $config
     * @param TimezoneInterface $timezone
     * @param NotificationEmailSender $emailSender
     * @param PayoneerNotificationRepositoryInterface $notificationRepository
     * @return void
     */
    public function __construct(
        CollectionFactory $notificationCollectionFactory,
        TransactionOrderUpdater $transactionOrderUpdater,
        NotificationLogger $notificationLogger,
        Config $config,
        TimezoneInterface $timezone,
        NotificationEmailSender $emailSender,
        PayoneerNotificationRepositoryInterface $notificationRepository
    ) {
        $this->notificationCollectionFactory = $notificationCollectionFactory;
        $this->transactionOrderUpdater = $transactionOrderUpdater;
        $this->notificationLogger = $notificationLogger;
        $this->config = $config;
        $this->timezone = $timezone;
        $this->emailSender = $emailSender;
        $this->notificationRepository = $notificationRepository;
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
                /** @phpstan-ignore-next-line */
                $notificationCollection->walk('delete');
            }
        } catch (\Exception $e) {
            $this->notificationLogger->addError(
                __('NotificationCleanupError: %1', $e->getMessage())
            );
        }
        try {
            $notificationCollection = $this->getNotificationsToSendEmail();
            if ($notificationCollection != null) {
                foreach ($notificationCollection as $notification) {
                    $response = \Safe\json_decode($notification->getContent(), true);
                    $this->emailSender->send($response);
                    $notification->setSendEmail(true);
                    $this->notificationRepository->save($notification);
                }
            }
        } catch (\Exception $e) {
            $this->notificationLogger->addError(
                __('NotificationEmailSendError: %1', $e->getMessage())
            );
        }
    }

    /**
     * Get all the notifications to cleanup
     *
     * @return Collection|null
     */
    private function getNotificationsToCleanUp()
    {
        $cleanupDays = $this->config->getValue(Config::NOTIFICATION_CLEANUP_DAYS_PATH);
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
        $emailSendDays = $this->config->getValue(Config::EMAIL_NOTIFICATION_DAYS_PATH);
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

    /**
     * Get the date in UTC timezone
     *
     * @param int $noOfDays
     * @return string
     */
    private function getUtcDateXDaysBefore($noOfDays)
    {
        return $this->timezone->date()->setTimezone(new \DateTimeZone('UTC'))
            ->modify('-' . $noOfDays . ' day')
            ->format('Y-m-d 23:59:59');
    }
}
