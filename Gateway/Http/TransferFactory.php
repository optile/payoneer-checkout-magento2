<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class TransferFactory
 * @package Payoneer\OpenPaymentGateway\Gateway\Http
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
     * @param array $request
     * @return TransferInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function create(array $request)
    {
        $merchantCode = $this->config->getConfig('merchant_gateway_key');
        $apiKey = $this->config->getCredentials('api_key');

        return $this->transferBuilder
            ->setBody($request)
            ->setAuthUsername($merchantCode)
            ->setAuthPassword($apiKey)
            ->setMethod(Config::METHOD_POST)
            ->setUri(Config::END_POINT)
            ->setHeaders(
                $this->config->prepareHeaders($merchantCode, $apiKey)
            )
            ->build();
    }
}
