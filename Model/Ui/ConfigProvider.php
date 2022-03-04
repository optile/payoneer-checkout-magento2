<?php

namespace Payoneer\OpenPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Helper;

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
     * @var Helper
     */
    private $helper;

    /**
     * ConfigProvider constructor.
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
                        'payment_flow' => $this->config->getValue('payment_flow'),
                        'widgetCssUrl' => $this->getStaticFilePath()
                    ]
                ]
            ]
        ];
    }

    /**
     * Get the widget css file path
     * @return string
     */
    public function getStaticFilePath()
    {
        $fileId = 'Payoneer_OpenPaymentGateway::css/widget.min.css';

        $params = [
            'area' => 'frontend'
        ];

        return $this->helper->getStaticFilePath($fileId, $params);
    }
}
