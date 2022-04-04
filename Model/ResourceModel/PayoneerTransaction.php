<?php

namespace Payoneer\OpenPaymentGateway\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * PayoneerTransaction resource model class
 */
class PayoneerTransaction extends AbstractDb
{
    /**
     * Table name
     */
    const TABLE = 'payoneer_payment_transaction';

    /**
     * Table primary key column name
     */
    const TABLE_PRIMARY_KEY = 'transaction_id';

    /**
     * Initialize the resource model with the table name
     * and the primary key column name.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(self::TABLE, self::TABLE_PRIMARY_KEY);
    }
}
