<?php

namespace Payoneer\OpenPaymentGateway\Model;

use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification as PayoneerNotificationResource;
use Magento\Framework\Model\AbstractModel;
use Payoneer\OpenPaymentGateway\Api\Data\NotificationInterface;

/**
 * PayoneerNotification class
 *
 * Payoneer_notification table model class
 */
class PayoneerNotification extends AbstractModel implements NotificationInterface
{
    /**
     * Initialize the resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(PayoneerNotificationResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getTransactionId()
    {
        return $this->_getData(NotificationInterface::TRANSACTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setTransactionId($txnId)
    {
        return $this->setData(NotificationInterface::TRANSACTION_ID, $txnId);
    }

    /**
     * @inheritDoc
     */
    public function getLongId()
    {
        return $this->_getData(NotificationInterface::LONG_ID);
    }

    /**
     * @inheritDoc
     */
    public function setLongId($longId)
    {
        return $this->setData(NotificationInterface::LONG_ID, $longId);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->_getData(NotificationInterface::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(NotificationInterface::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getContent()
    {
        return $this->_getData(NotificationInterface::CONTENT);
    }

    /**
     * @inheritDoc
     */
    public function setContent($response)
    {
        return $this->setData(NotificationInterface::CONTENT, $response);
    }

    /**
     * @inheritDoc
     */
    public function getCronStatus()
    {
        return $this->_getData(NotificationInterface::CRON_STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setCronStatus($status)
    {
        return $this->setData(NotificationInterface::CRON_STATUS, $status);
    }
}
