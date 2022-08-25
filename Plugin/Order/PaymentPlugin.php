<?php

namespace Payoneer\OpenPaymentGateway\Plugin\Order;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Model\Order\Payment;

/**
 * Class PaymentPlugin
 * Plugin to skip the invoice creation for a payoneer
 * cancelled orders.
 */
class PaymentPlugin
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
     * Around plugin will skip invoice creation for the
     * payoneer cancelled orders.
     *
     * @param Payment $subject
     * @param callable $proceed
     * @param null $invoice
     * @return Payment
     */
    public function aroundCapture(
        Payment $subject,
        callable $proceed,
        $invoice = null
    ) {
        $skipInvoiceCreation = $this->checkoutSession->getPayoneerSkipInvoiceCreation();
        if ($skipInvoiceCreation == true) {
            $this->checkoutSession->unsPayoneerSkipInvoiceCreation();
            return $subject;
        }
        return $proceed($invoice);
    }
}
