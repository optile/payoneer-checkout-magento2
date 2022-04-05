<?php

namespace Payoneer\OpenPaymentGateway\Api;

/**
 * @api
 * @since 100.0.2
 */
interface PayoneerNotificationRepositoryInterface
{
    /**
     * Save the notification data
     *
     * @param \Payoneer\OpenPaymentGateway\Api\Data\NotificationInterface $notification
     * @return \Payoneer\OpenPaymentGateway\Api\Data\NotificationInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(\Payoneer\OpenPaymentGateway\Api\Data\NotificationInterface $notification);
}
