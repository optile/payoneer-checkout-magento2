<?php

namespace Payoneer\OpenPaymentGateway\Observer;

use Magento\Checkout\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Updates order status
 */
class UpdateOrderStatusObserver implements ObserverInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Config
     */
    private $config;

    /**
     * UpdateOrderStatusObserver constructor.
     * @param Session $checkoutSession
     * @param Config $config
     */
    public function __construct(
        Session $checkoutSession,
        Config $config
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
    }

    /**
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->config->isPayoneerEnabled()) {
            return;
        }
        if ($this->checkoutSession->getUpdateOrderStatus() == true) {
            /** @var \Magento\Sales\Model\Order\Payment $orderPayment */
            $orderPayment = $observer->getEvent()->getPayment();
            $order = $orderPayment->getOrder();
            $grandTotal = $order->getGrandTotal();
            $orderPayment->setAdditionalInformation('amount', $grandTotal);
            $order->setStatus('payment_review')->setState('payment_review');
            $this->checkoutSession->unsUpdateOrderStatus();
        }
    }
}
