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
     * Builds request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $params = [];
        if ($this->checkoutSession->hasQuote()) {
            $params['cart_id'] = $this->checkoutSession->getQuoteId();
        }

        return [
            Config::CALLBACK => [
                Config::RETURN_URL => $this->urlBuilder->getUrl('payoneer/redirect/success', $params),
                Config::CANCEL_URL => $this->urlBuilder->getUrl('payoneer/redirect/cancel'),
                Config::NOTIFICATION_URL => 'https://dev.oscato.com/shop/notify',
            ]
        ];
    }
}
