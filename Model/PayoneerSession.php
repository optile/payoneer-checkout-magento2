<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Class PayoneerSession
 *
 * Module helper file for custom payoneer session
 */
class PayoneerSession
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * UnsetSession constructor.
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Unset custom checkout session variable
     * @return void
     */
    public function unsetPayoneerCheckoutSession()
    {
        if ($this->checkoutSession->getPayoneerCartUpdate()) {
            $this->checkoutSession->unsPayoneerCartUpdate();
        }
    }
}
