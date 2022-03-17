<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Helper;

/**
 * Class CustomerDataBuilder
 * Builds customer data
 */
class CustomerDataBuilder implements BuilderInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @param Helper $helper
     * @param Session $checkoutSession
     */
    public function __construct(
        Helper $helper,
        Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
    }
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();

        $registrationId = null;
        $customerId = $order->getCustomerId();
        if ($customerId) {
            $registrationId = $this->helper->getRegistrationId($customerId);
        }

        $customerEmail = $billingAddress->getEmail();
        if (!$customerEmail) {
            $customerEmail = $this->checkoutSession->getPayoneerCustomerEmail();
        }

        $customerData = [
            Config::CUSTOMER    => [
                Config::NUMBER  => $billingAddress->getTelephone(),
                Config::EMAIL   => $customerEmail ,
                Config::COMPANY => [
                    Config::NAME    => $billingAddress->getCompany(),
                ],
                Config::NAME    => [
                    Config::FIRST_NAME  => $billingAddress->getFirstname(),
                    Config::MIDDLE_NAME => $billingAddress->getMiddlename(),
                    Config::LAST_NAME   => $billingAddress->getLastname()
                ]
            ]
        ];

        if ($registrationId) {
            $customerData[Config::CUSTOMER][Config::REGISTRATION] = [
                Config::ID  => $registrationId
            ];
        }

        return $customerData;
    }
}
