<?php

namespace Payoneer\OpenPaymentGateway\Controller\Embedded;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\UrlInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class Style
 * Process Style data for embedded integration
 */
class Style implements HttpGetActionInterface
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Style constructor.
     * @param RawFactory $resultRawFactory
     * @param UrlInterface $urlBuilder
     * @param Config $config
     */
    public function __construct(
        RawFactory $resultRawFactory,
        UrlInterface $urlBuilder,
        Config $config
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
    }

    /**
     * Creates raw css file based on configuration
     * @return ResponseInterface|Raw|ResultInterface
     */
    public function execute()
    {
        $styleConfig = $this->config->getStyleConfig();
        $containerCSS = $this->getContainerStyle($styleConfig);
        $containerPlaceholderCSS = $this->getContainerPlaceholderStyle();
        $checkoutCssConfig = $this->config->getValue('widget_appearance/checkout_css');

        $widgetCSS = $containerCSS . $containerPlaceholderCSS;
        if ($checkoutCssConfig) {
            $widgetCSS = $widgetCSS . $checkoutCssConfig;
        }

        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setHeader('Content-type', 'text/css');
        $resultRaw->setContents($widgetCSS);

        return $resultRaw;
    }

    /**
     * Get formatted css for the container
     * @param $styleConfig
     * @return string
     */
    public function getContainerStyle($styleConfig)
    {
        $content = '#networkForm, .op-payment-widget-container {';
        foreach ($styleConfig as $key => $value) {
            $content = $content . $key . ':' . $value . ';';
        }
        return $content . '}';
    }

    /**
     * Get placeholder css
     * @return string
     */
    public function getContainerPlaceholderStyle()
    {
        $inputStyle = '';
        $placeholderValue = $this->config->getValue('widget_appearance/placeholders_color');
        $phContent = '#networkForm ::placeholder, .op-payment-widget-container ::placeholder {';
        $phContent = $phContent . 'opacity: 1;';
        if ($placeholderValue) {
            $phContent = $phContent . 'color:' . $placeholderValue . ';';
            $inputStyle = '#networkForm ::-ms-input-placeholder, .op-payment-widget-container ::-ms-input-placeholder{'
                . $phContent . '}';
        }
        $phContent = $phContent . '}';
        if ($inputStyle) {
            $phContent = $phContent . $inputStyle;
        }
        return $phContent;
    }
}
