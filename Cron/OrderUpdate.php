<?php

namespace Payoneer\OpenPaymentGateway\Cron;

use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification\CollectionFactory;
use Payoneer\OpenPaymentGateway\Model\TransactionOrderUpdater;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification\Collection;

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
     * OrderUpdate construct function
     *
     * @param CollectionFactory $notificationCollectionFactory
     * @param TransactionOrderUpdater $transactionOrderUpdater
     * @return void
     */
    public function __construct(
        CollectionFactory $notificationCollectionFactory,
        TransactionOrderUpdater $transactionOrderUpdater
    ) {
        $this->notificationCollectionFactory = $notificationCollectionFactory;
        $this->transactionOrderUpdater = $transactionOrderUpdater;
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
                    continue;
                }
                $notification->setCronStatus(1);
                $notification->save();
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
        $collection->setOrder('created_at', 'DESC');

        return $collection;
    }
}
