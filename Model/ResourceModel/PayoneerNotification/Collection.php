<?php

namespace Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Payoneer\OpenPaymentGateway\Model\PayoneerNotification as PayoneerNotificationModel;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerNotification as PayoneerNotificationResource;

class Collection extends AbstractCollection
{
    /**
     * Initialize the payoneer notification
     * collection class
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(PayoneerNotificationModel::class, PayoneerNotificationResource::class);
    }
}
