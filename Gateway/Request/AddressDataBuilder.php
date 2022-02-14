<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class AddressDataBuilder
 * Class to build address data
 */
class AddressDataBuilder implements BuilderInterface
{
    /**
     * Builds address data
     *
     * @param array <mixed> $buildSubject
     * @return array <mixed>
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
                    Config::SHIPPING => $this->getAddressData($shippingAddress),
                    Config::BILLING => $this->getAddressData($billingAddress)
                ]
            ]
        ];
    }

    /**
     * Gets address details
     *
     * @param AddressAdapterInterface $address
     * @return array <mixed>
     */
    public function getAddressData($address)
    {
        return [
            Config::STREET => $address->getStreetLine1(),
            Config::HOUSE_NUMBER => $address->getStreetLine2(),
            Config::ZIP => $address->getPostcode(),
            Config::CITY => $address->getCity(),
            Config::STATE => $address->getRegionCode(),
            Config::COUNTRY => $address->getCountryId(),
            Config::NAME => [
                Config::FIRST_NAME => $address->getFirstname(),
                Config::MIDDLE_NAME => $address->getMiddlename(),
                Config::LAST_NAME => $address->getLastname()
            ]
        ];
    }
}
