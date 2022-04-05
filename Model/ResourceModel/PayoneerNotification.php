<?php

namespace Payoneer\OpenPaymentGateway\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Payoneer\OpenPaymentGateway\Api\Data\NotificationInterface;

/**
 * PayoneerNotification resource model class
 */
class PayoneerNotification extends AbstractDb
{
    /**
     * Initialize the resource model with the table name
     * and the primary key column name.
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(NotificationInterface::TABLE, NotificationInterface::ID);
    }
}
