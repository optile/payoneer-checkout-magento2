<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

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
     * @var string
     */
    protected $method;

    /**
     * @param TransferBuilder $transferBuilder
     * @param Config $config
     * @param string $method
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        Config $config,
        $method
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->config = $config;
        $this->method = $method;
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
        if ($this->method == Config::METHOD_POST) {
            return Config::LIST_END_POINT;
        } else {
            $additionalInformation = $payment->getPayment()->getAdditionalInformation();
            $listId = isset($additionalInformation['listId']) ? $additionalInformation['listId'] : null;

            return sprintf(
                Config::LIST_UPDATE_END_POINT,
                $listId
            );
        }
    }

    /**
     * Return the method
     *
     * @return string
     */
    protected function getMethod()
    {
        return $this->method;
    }
}
