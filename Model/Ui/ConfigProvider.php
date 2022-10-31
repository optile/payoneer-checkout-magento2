<?php

namespace Payoneer\OpenPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Helper;

/**
 * Class ConfigProvider - Payoneer configuration class
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'payoneer';
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

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
        StoreManagerInterface $storeManager,
        Config $config,
        Helper $helper
    ) {
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array <mixed>
     * @throws NoSuchEntityException
     */
    public function getConfig()
    {
        /** @var Store $store */
        $store = $this->storeManager->getStore();
        return [
            'payment' => [
                self::CODE => [
                    'config' => [
                        'active' => (bool)$this->config->getValue('active'),
                        'environment' => $this->config->getValue('environment'),
                        'payment_flow' => $this->config->getValue('payment_flow'),
                        'widgetCssUrl' => $this->getStaticFilePath(),
                        'payment_icon_type' => $this->config->getValue('widget_appearance/payment_icon_type'),
                        'processPaymentUrl' => $store->getUrl(
                            'payoneer/integration/processpayment',
                            ['_secure' => $store->isCurrentlySecure()]
                        ),
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
