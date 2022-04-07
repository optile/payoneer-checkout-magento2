<?php

namespace Payoneer\OpenPaymentGateway\Model;

use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerTransaction as PayoneerTransactionResource;
use Payoneer\OpenPaymentGateway\Api\Data\PayoneerTransactionInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * PayoneerTransaction class
 *
 * payoneer_payment_transaction table model class
 */
class PayoneerTransaction extends AbstractModel implements PayoneerTransactionInterface
{
    /**
     * Initialize the resource model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(PayoneerTransactionResource::class);
    }

    /**
     * @inheritDoc
     */
    public function getTransactionId()
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setTransactionId($transactionId)
    {
        $this->setData(self::TRANSACTION_ID, $transactionId);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId($customerId)
    {
        $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getRegistrationId()
    {
        return $this->getData(self::REGISTRATION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setRegistrationId($registrationId)
    {
        $this->setData(self::REGISTRATION_ID, $registrationId);
    }
}
