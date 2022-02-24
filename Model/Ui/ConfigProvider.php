<?php

namespace Payoneer\OpenPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Http\Client\Client;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class ConfigProvider - Payoneer configuration class
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'payoneer';

    /**
     * @var Config
     */
    protected $config;

    /**
     * ConfigProvider constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'config' => [
                        'active' => (bool)$this->config->getValue('active'),
                        'payment_flow' => $this->config->getValue('payment_flow')
                    ]
                ]
            ]
        ];
    }
}
