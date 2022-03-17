<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Checkout\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class CallBackBuilder
 * Builds Callback array
 */
class CallBackDataBuilder implements BuilderInterface
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
     * Builds callback data
     *
     * @param array <mixed> $buildSubject
     * @return array <mixed>
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);
        $token = $payment->getPayment()->getAdditionalInformation('token');
        $orderId = $payment->getOrder()->getOrderIncrementId();

        $successParams = [];
        $cancelParams = ['error' => true, 'token' => $token];

        if ($this->checkoutSession->hasQuote()) {
            $successParams['cart_id'] = $this->checkoutSession->getQuoteId();
            $successParams['token'] = $token;
        }

        $notificationParams = ['order_id' => $orderId, 'token' => $token];

        return [
            Config::CALLBACK => [
                Config::RETURN_URL => $this->urlBuilder->getUrl(Config::RETURN_URL_PATH, $successParams),
                Config::CANCEL_URL => $this->urlBuilder->getUrl(Config::CANCEL_URL_PATH, $cancelParams),
                Config::NOTIFICATION_URL => $this->urlBuilder->getUrl(
                    Config::NOTIFICATION_URL_PATH,
                    $notificationParams
                )
            ]
        ];
    }
}
