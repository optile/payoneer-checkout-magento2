<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class StyleDataBuilder
 * Builds Style data array
 */
class StyleDataBuilder implements BuilderInterface
{
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @param UrlInterface $urlBuilder
     * @param Config $config
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Config $config
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        $styleData = [
            Config::STYLE => [
                Config::HOSTED_VERSION => Config::VERSION_V4
            ]
        ];

        if ($this->config->isHostedIntegration()) {
            return $styleData;
        } else {
            $styleDataValues = $this->config->getStyleConfig();
            $styleData[Config::STYLE]['resolution'] = Config::RESOLUTION_1X;
            $styleData[Config::STYLE]['primaryColor'] = $styleDataValues['background-color'];
            $styleData[Config::STYLE]['cssOverride'] =
              //"https://payoneer.local.tryzens.net/static/version1646060873/frontend/Magento/luma/en_US/Payoneer_OpenPaymentGateway/css/test.css";
            $this->urlBuilder->getUrl(Config::EMBEDDED_STYLE_PATH);
        }

        return $styleData;
    }
}
