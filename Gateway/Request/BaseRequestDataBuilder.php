<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Checkout\Model\Session;
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
     * @var Session
     */
    private $checkoutSession;

    /**
     * @param Config $config
     * @param Session $checkoutSession
     */
    public function __construct(
        Config $config,
        Session $checkoutSession
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
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
        $billingAddress = $payment->getOrder()->getBillingAddress();

        if (isset($buildSubject['address']['countryId'])) {
            $countryId = $buildSubject['address']['countryId'];
        }
        $countryId = $countryId ?: ($billingAddress ? $billingAddress->getCountryId() : null);
        if ($countryId) {
            $this->checkoutSession->setBillingCountryId($countryId);
        }
        $countryId = $countryId ?: $this->checkoutSession->getBillingCountryId();
        return [
            Config::TRANSACTION_ID  => $payment->getPayment()->getAdditionalInformation(Config::TXN_ID),
            Config::COUNTRY         => $countryId,
            Config::INTEGRATION     => $this->config->getValue('payment_flow'),
            Config::DIVISION        => $this->config->getValue('environment') == Fields::ENVIRONMENT_SANDBOX_VALUE
                ? $this->config->getValue('sandbox_store_code')
                : $this->config->getValue('live_store_code'),
            Config::ALLOW_DELETE    => true
        ];
    }
}
