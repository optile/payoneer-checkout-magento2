<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class MagentoTransferFactory
 *
 * Builds gateway transfer object
 */
class MagentoTransferFactory implements TransferFactoryInterface
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
     * @return TransferInterface
     */
    public function create(array $request)
    {
        return $this->transferBuilder
            ->setBody($request)
            ->setMethod(Config::METHOD_POST)
            ->setHeaders(
                [
                    Config::TXN_ID => $request[Config::TXN_ID]
                ]
            )
            ->build();
    }
}
