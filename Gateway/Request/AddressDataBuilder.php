<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

class AddressDataBuilder implements BuilderInterface
{
    /**
     * Builds address data
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        $order = $payment->getOrder();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        return [
            Config::CUSTOMER => [
                Config::ADDRESSES => [
                    Config::SHIPPING => [
                        'street' => 'Ganghoferstr.',
                        'houseNumber' => '39',
                        'zip' => '80339',
                        'city' => 'Munich',
                        'state' => 'Bayern',
                        'country' => 'DE',
                        'name' => [
                            'title' => 'Mr.',
                            'firstName' => 'James',
                            'middleName' => 'Junior',
                            'lastName' => 'Blond',
                            'maidenName' => 'string',
                        ],
                    ],
                    Config::BILLING => [
                        'street' => 'Ganghoferstr.',
                        'houseNumber' => '39',
                        'zip' => '80339',
                        'city' => 'Munich',
                        'state' => 'Bayern',
                        'country' => 'DE',
                        'name' => [
                            'title' => 'Mr.',
                            'firstName' => 'James',
                            'middleName' => 'Junior',
                            'lastName' => 'Blond',
                            'maidenName' => 'string',
                        ],
                    ]
                ]
            ]
        ];
    }
}
