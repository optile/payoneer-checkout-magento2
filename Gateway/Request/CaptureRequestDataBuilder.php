<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

class CaptureRequestDataBuilder implements BuilderInterface
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
     * Builds ENV request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);
        $order = $payment->getOrder();

        return [
            'TXN_TYPE' => 'S',
            'TXN_ID' => $order->getOrderIncrementId(),
            'MERCHANT_KEY' => $this->config->getValue(
                'merchant_gateway_key',
                $order->getStoreId()
            )
        ];
    }
}
