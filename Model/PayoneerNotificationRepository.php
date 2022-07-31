<?php

namespace Payoneer\OpenPaymentGateway\Model;

use Exception;
use Magento\Framework\Exception\CouldNotSaveException;
use Payoneer\OpenPaymentGateway\Api\Data\NotificationInterface;
use Payoneer\OpenPaymentGateway\Api\PayoneerNotificationRepositoryInterface;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification;
use Payoneer\OpenPaymentGateway\Model\PayoneerNotification as PayoneerNotificationModel;

class PayoneerNotificationRepository implements PayoneerNotificationRepositoryInterface
{
    /**
     * @var PayoneerNotification
     */
    private $notificationResource;

    /**
     * PayoneerNotificationRepository construct
     *
     * @param PayoneerNotification $notificationResource
     * @return void
     */
    public function __construct(
        PayoneerNotification $notificationResource
    ) {
        $this->notificationResource = $notificationResource;
    }

    /**
     * @inheriDoc
     */
    public function save(NotificationInterface $notification)
    {
        try {
            /** @var PayoneerNotificationModel $notification */
            $this->notificationResource->save($notification);
            return $notification;
        } catch (Exception $e) {
            throw new CouldNotSaveException(
                __('We couldn\'t save the notification. Try again later.')
            );
        }
    }
}
