<?php

namespace Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerTransaction;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Payoneer\OpenPaymentGateway\Model\PayoneerTransaction as PayoneerTransactionModel;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerTransaction as PayoneerTransactionResource;

/**
 * Payoneer transaction collection class
 */
class Collection extends AbstractCollection
{
    protected $_idFieldName = 'transaction_id';

    /**
     * Initialize the payoneer transaction
     * collection class
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(PayoneerTransactionModel::class, PayoneerTransactionResource::class);
    }
}
