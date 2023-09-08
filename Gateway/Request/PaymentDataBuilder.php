<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Payoneer\OpenPaymentGateway\Model\Helper;

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
     * @var Helper
     */
    protected $helper;

    /**
     * @param Config $config
     * @param Helper $helper
     */
    public function __construct(
        Config $config,
        Helper $helper
    ) {
        $this->config = $config;
        $this->helper = $helper;
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

        return $this->getPaymentData($order, $buildSubject);
    }

    /**
     * Builds payment data along with MOR Data
     *
     * @param OrderAdapterInterface $order
     * @param array <mixed> $buildSubject
     * @return array <mixed>
     */
    private function getPaymentData($order, $buildSubject): array
    {
        return [
            Config::PAYMENT => [
                Config::AMOUNT      => number_format($buildSubject[Config::AMOUNT], 2),
                Config::CURRENCY    => $order->getCurrencyCode(),
                Config::REFERENCE   => $this->config->getValue('order_reference_message'),
                Config::INVOICE_ID  => $order->getId(),
                Config::TAX_AMOUNT  => $this->helper->formatNumber($order->getTaxAmount())
            ]
        ];
    }
}
