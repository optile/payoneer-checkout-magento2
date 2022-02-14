<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class CustomerDataBuilder
 * Builds customer data
 */
class CustomerDataBuilder implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();

        return [
            Config::CUSTOMER => [
                Config::NUMBER => $billingAddress->getTelephone(),
                Config::EMAIL => $billingAddress->getEmail(),
                Config::COMPANY => [
                    Config::NAME => $billingAddress->getCompany(),
                ],
                Config::NAME => [
                    Config::FIRST_NAME => $billingAddress->getFirstname(),
                    Config::MIDDLE_NAME => $billingAddress->getMiddlename(),
                    Config::LAST_NAME => $billingAddress->getLastname()
                ]
            ]
        ];
    }
}
