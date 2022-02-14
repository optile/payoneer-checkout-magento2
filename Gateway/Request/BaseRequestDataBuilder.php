<?php
namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Source\Fields;

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
        $payment = SubjectReader::readPayment($buildSubject);
        return [
            Config::TRANSACTION_ID => $payment->getPayment()->getAdditionalInformation('transaction_id') ?:
                $payment->getOrder()->getId(),
            Config::COUNTRY => $payment->getOrder()->getBillingAddress()->getCountryId(),
            Config::INTEGRATION=> $this->config->getValue('payment_flow'),
            Config::DIVISION => $this->config->getValue('environment') == Fields::ENVIRONMENT_SANDBOX ?
                $this->config->getValue('sandbox_store_code') :
                $this->config->getValue('live_store_code')
        ];
    }
}
