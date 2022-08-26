<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Source\Fields;

/**
 * Class BaseRequestDataBuilder
 * Builds base request data
 */
class BaseRequestDataBuilder implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds base request data
     *
     * @param array <mixed> $buildSubject
     * @return array <mixed>
     */
    public function build(array $buildSubject)
    {
        $countryId = null;
        $payment = SubjectReader::readPayment($buildSubject);

        return [
            Config::TRANSACTION_ID  => $payment->getPayment()->getAdditionalInformation(Config::TXN_ID),
            Config::COUNTRY         => $this->getCountryId($payment),
            Config::INTEGRATION     => $this->config->getValue('payment_flow'),
            Config::DIVISION        => $this->config->getValue('environment') == Fields::ENVIRONMENT_SANDBOX_VALUE
                ? $this->config->getValue('sandbox_store_code')
                : $this->config->getValue('live_store_code'),
            Config::ALLOW_DELETE    => true
        ];
    }

    /**
     * @param $payment
     * @return string
     */
    private function getCountryId($payment)
    {
        $order = $payment->getOrder();

        //use country of shipping address if it exists, else billing address or store country
        $shippingAddress = $order->getShippingAddress();
        if($shippingAddress) {
            $countryId = $shippingAddress->getCountryId();
        } else {
            $billingAddress = $order->getBillingAddress();
            if($billingAddress) {
                $countryId = $billingAddress->getCountryId();
            }
            else {
                $countryId = $this->config->getCountryByStore();
            }
        }
        return $countryId;
    }
}
