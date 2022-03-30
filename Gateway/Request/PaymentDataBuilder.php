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
     * Builds payment data
     *
     * @param array <mixed> $buildSubject
     * @return array <mixed>
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);
        $order = $payment->getOrder();

        return [
            Config::PAYMENT => [
                Config::AMOUNT      => number_format($buildSubject[Config::AMOUNT], 2),
                Config::CURRENCY    => $order->getCurrencyCode(),
                Config::REFERENCE   => $this->config->getValue('order_reference_message'),
                Config::INVOICE_ID  => $order->getId()
            ]
        ];
    }
}
