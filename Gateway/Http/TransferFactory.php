<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class TransferFactory
 *
 * Builds gateway transfer object
 */
class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    protected $transferBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param TransferBuilder $transferBuilder
     * @param Config $config
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Config $config
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array <mixed> $request
     * @param PaymentDataObjectInterface $payment
     * @return TransferInterface
     */
    public function create(array $request, PaymentDataObjectInterface $payment)
    {
        $merchantCode = $this->config->getValue('merchant_gateway_key');
        $apiKey = $this->config->getCredentials('api_key');
        $hostName = $this->config->getCredentials('host_name');

        return $this->transferBuilder
            ->setBody($request)
            ->setAuthUsername($merchantCode)
            ->setAuthPassword($apiKey)
            ->setMethod($this->getMethod())
            ->setUri($this->getApiUri($payment))
            ->setHeaders(
                $this->config->prepareHeaders($merchantCode, $apiKey)
            )->setClientConfig(['host_name' => $hostName])
            ->build();
    }

    /**
     * Return the api uri
     *
     * @param PaymentDataObjectInterface $payment
     * @return string
     */
    protected function getApiUri(PaymentDataObjectInterface $payment)
    {
        return Config::END_POINT;
    }

    /**
     * Return the method
     *
     * @return string
     */
    protected function getMethod()
    {
        return Config::METHOD_POST;
    }
}
