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
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $countryId = '';
        $payment = SubjectReader::readPayment($buildSubject);

        if (isset($buildSubject['address']['countryId'])) {
            $countryId = $buildSubject['address']['countryId'];
        }

        return [
            Config::TRANSACTION_ID => $payment->getPayment()->getAdditionalInformation('transaction_id') ?:
                $payment->getOrder()->getOrderIncrementId(),
            Config::COUNTRY => $countryId ?: $payment->getOrder()->getBillingAddress()->getCountryId(),
            Config::INTEGRATION => $this->config->getValue('payment_flow'),
            Config::DIVISION => $this->config->getValue('environment') == Fields::ENVIRONMENT_SANDBOX_VALUE ?
                $this->config->getValue('sandbox_store_code') :
                $this->config->getValue('live_store_code')
        ];
    }
}
