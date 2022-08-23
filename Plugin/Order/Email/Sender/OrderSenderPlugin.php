<?php

namespace Payoneer\OpenPaymentGateway\Plugin\Order\Email\Sender;

use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order;

/**
 * Class OrderSenderPlugin
 * Plugin to skip order confirmation email sending
 * for cancelled orders.
 */
class OrderSenderPlugin
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * Construct function
     *
     * @param CheckoutSession $checkoutSession
     * @return void
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Around plugin will skip order sending if the order is a payoneer
     * cancel order, else do the email sending.
     *
     * @param OrderSender $subject
     * @param callable $proceed
     * @param Order $order
     * @param boolean $forceSyncMode
     * @return boolean
     */
    public function aroundSend(OrderSender $subject, callable $proceed, Order $order, $forceSyncMode = false)
    {
        $isCancelledOrder = $this->checkoutSession->getIsPayoneerCancelledOrder();
        if ($isCancelledOrder == true) {
            $this->checkoutSession->unsIsPayoneerCancelledOrder();
            return false;
        }
        return $proceed($order, $forceSyncMode);
    }
}
