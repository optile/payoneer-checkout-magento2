<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Checkout\Model\Session;
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
     * Billing address data constants
     */
    const FIRST_NAME    =   'firstname';
    const LAST_NAME     =   'lastname';
    const MIDDLE_NAME   =   'middlename';
    const EMPTY_STRING  =   '';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @param Session $checkoutSession
     */
    public function __construct(
        Session $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

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
        $billingAddressChanged = false;
        $address = isset($buildSubject['address']) ? $buildSubject['address'] : null;
        if ($address) {
            $billingAddress = $address;
            $billingAddressChanged = true;
        }

        $shippingAddressCountryId = $shippingAddress ? $shippingAddress->getCountryId() : null;
        if ($shippingAddressCountryId) {
            $this->checkoutSession->setShippingCountryId($shippingAddressCountryId);
        }

        return [
            Config::CUSTOMER => [
                Config::ADDRESSES => [
                    Config::SHIPPING => $shippingAddress ? $this->getAddressData($shippingAddress) : [],
                    Config::BILLING => $billingAddressChanged ?
                        $this->getNewBillingAddress($billingAddress) :
                        $this->getAddressData($billingAddress)
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
            Config::COUNTRY => $address->getCountryId() ?: $this->checkoutSession->getShippingCountryId(),
            Config::NAME => [
                Config::FIRST_NAME => $address->getFirstname(),
                Config::MIDDLE_NAME => $address->getMiddlename(),
                Config::LAST_NAME => $address->getLastname()
            ]
        ];
    }

    /**
     * Build new billing address data
     * @param array <mixed> $billingAddress
     * @return array <mixed>
     */
    public function getNewBillingAddress($billingAddress)
    {
        $billingAddressCountryId = isset($billingAddress[Config::COUNTRY_ID]) ?
            $billingAddress[Config::COUNTRY_ID] : $this->checkoutSession->getBillingCountryId();

        return [
            Config::STREET => isset($billingAddress[Config::STREET][0]) ?
                $billingAddress[Config::STREET][0] : self::EMPTY_STRING,
            Config::HOUSE_NUMBER => isset($billingAddress[Config::STREET][1]) ?
                $billingAddress[Config::STREET][1] : self::EMPTY_STRING,
            Config::ZIP => isset($billingAddress[Config::POSTCODE]) ?
                $billingAddress[Config::POSTCODE] : self::EMPTY_STRING,
            Config::CITY => isset($billingAddress[Config::CITY]) ?
                $billingAddress[Config::CITY] : self::EMPTY_STRING,
            Config::STATE => isset($billingAddress[Config::REGION]) ?
                $billingAddress[Config::REGION] : self::EMPTY_STRING,
            Config::COUNTRY => $billingAddressCountryId,
            Config::NAME => [
                Config::FIRST_NAME => isset($billingAddress[self::FIRST_NAME]) ?
                    $billingAddress[self::FIRST_NAME] : self::EMPTY_STRING,
                Config::MIDDLE_NAME => isset($billingAddress[self::MIDDLE_NAME]) ?
                    $billingAddress[self::MIDDLE_NAME] : self::EMPTY_STRING,
                Config::LAST_NAME => isset($billingAddress[self::LAST_NAME]) ?
                    $billingAddress[self::LAST_NAME] : self::EMPTY_STRING
            ]
        ];
    }
}
