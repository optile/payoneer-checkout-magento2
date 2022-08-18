<?php

namespace Payoneer\OpenPaymentGateway\Controller\Embedded;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Controller\ResultInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Config\Source\PaymentIconType;

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
     * @var Config
     */
    protected $config;

    /**
     * Style constructor.
     * @param RawFactory $resultRawFactory
     * @param Config $config
     */
    public function __construct(
        RawFactory $resultRawFactory,
        Config $config
    ) {
        $this->resultRawFactory = $resultRawFactory;
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
        $paymentIconStyle = $this->getPaymentIconStyle();

        $widgetCSS = $containerCSS . $containerPlaceholderCSS . $paymentIconStyle;
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
     * @param array <mixed> $styleConfig
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
        $selectStyle = '';
        $placeholderValue = $this->config->getValue('widget_appearance/placeholders_color');
        $phContentClass = '#networkForm ::placeholder, .op-payment-widget-container ::placeholder {';
        $phContent = $phContentClass . 'opacity: 1;';
        if ($placeholderValue) {
            $phColor = 'color:' . $placeholderValue . ';';
            $phContent = $phContent . $phColor;
            $inputStyle = '#networkForm ::-ms-input-placeholder, .op-payment-widget-container ::-ms-input-placeholder{'
                . $phColor . '}';
            $selectStyle = '#networkForm select {' . $phColor . '}';
        }
        $phContent = $phContent . '}';
        if ($inputStyle) {
            $phContent = $phContent . $inputStyle . $selectStyle;
        }
        return $phContent;
    }

    /**
     * If the payment icon type is static, then return
     * css to hide the dynamic payment icon.
     *
     * @return string
     */
    private function getPaymentIconStyle()
    {
        $paymentIconType = $this->config->getValue('widget_appearance/payment_icon_type');
        if ($paymentIconType == PaymentIconType::PAYMENT_ICON_STATIC) {
            return '.op-payment-widget-container > div.list > label.imgLabel{display: none;}';
        }
        return '';
    }
}
