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
        $content = '.payment-networks-container{';
        foreach ($styleConfig as $key=>$value) {
            $content = $content . $key . ':' . $value . ';';
        }
        $content = $content . '}';
        $resultRaw = $this->resultRawFactory->create();
        $resultRaw->setHeader('Content-type', 'text/css');
        $resultRaw->setContents($content);

        return $resultRaw;
    }
}
