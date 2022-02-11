<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

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
     */
    public function create(array $request)
    {
        $headers = [];
        $headers['Content-Type'] = 'application/vnd.optile.payment.enterprise-v1-extensible+json';
        $headers['Accept'] = 'application/vnd.optile.payment.enterprise-v1-extensible+json';
        $headers['Authorization'] = 'Basic ' . base64_encode('MRS_TEST_TRYZENS' . ':' . 'v3e7es43uj3qnfocl2thi5ccle245ta7g38s03t1');

        return $this->transferBuilder
            ->setBody($request)
            ->setAuthUsername($this->config->getConfig('merchant_gateway_key'))
            ->setAuthPassword($this->config->getConfig('sandbox_api_key'))
            ->setMethod(Config::METHOD_POST)
            ->setUri(Config::END_POINT)
            ->setHeaders(
                $headers
            )
            ->build();
    }
}
