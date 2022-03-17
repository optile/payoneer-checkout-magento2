<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class PaymentDataBuilder
 * Builds payment data
 */
class PaymentDataBuilder implements BuilderInterface
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
        $order = $payment->getOrder();

        return [
            Config::PAYMENT => [
                Config::AMOUNT      => $buildSubject[Config::AMOUNT],
                Config::CURRENCY    => $order->getCurrencyCode(),
                Config::REFERENCE   => $this->config->getValue('order_reference_message'),
                Config::INVOICE_ID  => $order->getId()
            ]
        ];
    }
}
