<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class CallBackBuilder
 * Builds 'Callback' array
 */
class CallBackBuilder implements BuilderInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * CallBackBuilder constructor.
     * @param UrlInterface $urlBuilder
     * @param Session $checkoutSession
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Session $checkoutSession
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $successParams = [];
        $cancelParams = ['error' => true];
        if ($this->checkoutSession->hasQuote()) {
            $successParams['cart_id'] = $this->checkoutSession->getQuoteId();
            $successParams['token'] = 'sdfgfgsfdg';//todo
        }

        return [
            Config::CALLBACK => [
                Config::RETURN_URL => $this->urlBuilder->getUrl(Config::RETURN_URL_PATH, $successParams),
                Config::CANCEL_URL => $this->urlBuilder->getUrl(Config::CANCEL_URL_PATH, $cancelParams),
                Config::NOTIFICATION_URL => $this->urlBuilder->getUrl(Config::NOTIFICATION_URL_PATH)
            ]
        ];
    }
}
