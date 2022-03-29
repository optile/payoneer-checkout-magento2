<?php

namespace Payoneer\OpenPaymentGateway\Plugin\Adminhtml;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Helper;

/**
 * Class SalesOrderViewPlugin
 * Add Payoneer action buttons
 */
class OrderViewPlugin
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * OrderViewPlugin constructor.
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Add Payoneer action buttons
     *
     * @param OrderView $subject
     * @return void
     */
    public function beforeSetLayout(OrderView $subject)
    {
        if ($this->helper->isPayoneerEnabled()) {
            $order = $subject->getOrder();
            if ($this->helper->canShowCaptureBtn($order)) {
                $subject->addButton(
                    'payoneer_capture',
                    [
                    'label' => __('Payoneer Capture'),
                    'class' => __('action-default scalable'),
                    'id' => 'order-view-payoneer-capture-button',
                    'onclick' => 'setLocation(\'' . $this->getCaptureUrl($subject) . '\')'
                    ]
                );
            }
        }
    }

    /**
     * Get URL for Payoneer Capture
     *
     * @param OrderView $subject
     * @return mixed
     */
    protected function getCaptureUrl($subject)
    {
        return $subject->getUrl('payoneer/gateway/capture/order_id/' . $subject->getOrderId());
    }
}
