<?php

namespace Payoneer\OpenPaymentGateway\Api;

use Payoneer\OpenPaymentGateway\Api\Data\NotificationInterface;

/**
 * @api
 * @since 100.0.2
 */
interface PayoneerNotificationRepositoryInterface
{
    /**
     * Save the notification data
     *
     * @param NotificationInterface $notification
     * @return NotificationInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(NotificationInterface $notification);
}
