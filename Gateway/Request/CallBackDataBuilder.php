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
     * @var Config
     */
    protected $config;

    /**
     * CallBackBuilder constructor.
     * @param UrlInterface $urlBuilder
     * @param Session $checkoutSession
     * @param Config $config
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Session $checkoutSession,
        Config $config
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
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
        $token = $payment->getPayment()->getAdditionalInformation(Config::TOKEN);
        $notificationToken = $payment->getPayment()->getAdditionalInformation(Config::TOKEN_NOTIFICATION);
        $orderId = $payment->getOrder()->getOrderIncrementId();

        $successParams = ['cart_id' => $this->getQuoteIdFromSession(), 'token' => $token];
        $cancelParams = ['error' => true];

        return [
            Config::CALLBACK => [
                Config::RETURN_URL => $this->urlBuilder->getUrl(Config::RETURN_URL_PATH, $successParams),
                Config::CANCEL_URL => $this->urlBuilder->getUrl(Config::CANCEL_URL_PATH, $cancelParams),
                Config::NOTIFICATION_URL => $this->getNotificationUrl($orderId, $notificationToken)
            ]
        ];
    }

    /**
     * @param string $orderId
     * @param mixed $token
     * @return string
     */
    public function getNotificationUrl($orderId, $token)
    {
        $configUrl = $this->config->getValue('notification_url');
        if ($configUrl) {
            $configUrl = $configUrl . '/order_id/' . $orderId . '/token/' . $token;
        } else {
            $configUrl =  $this->urlBuilder->getUrl(
                Config::NOTIFICATION_URL_PATH,
                ['order_id' => $orderId, 'token' => $token]
            );
        }
        return $configUrl;
    }

    /**
     * Gets quote id from the checkout session
     * @return int|null
     */
    public function getQuoteIdFromSession()
    {
        $quoteId = null;
        if ($this->checkoutSession->hasQuote()) {
            $quoteId = $this->checkoutSession->getQuoteId();
        }
        return $quoteId;
    }
}
