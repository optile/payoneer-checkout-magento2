<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Response;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Class PayoneerResponseHandler
 *
 * Payoneer gateway response handler
 */
class PayoneerResponseHandler implements HandlerInterface
{
    const ADDITIONAL_INFO_KEY_REFUND_RESPONSE = 'refund_response';
    const ADDITIONAL_INFO_KEY_PARTIAL_REFUND_RESPONSE = 'partial_refund_response';
    const ADDITIONAL_INFO_KEY_CAPTURE_RESPONSE = 'capture_response';
    const ADDITIONAL_INFO_KEY_AUTH_CANCEL_RESPONSE = 'auth_cancel_response';

    const AUTH_CANCEL_STATUS_NODE = 'auth_cancel_status';
    const AUTH_CAPTURE_STATUS_NODE = 'capture_status';
    const REFUND_TXN_TYPE =   'refund';

    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * @var mixed|string
     */
    private $additionalInfoKey;

    /**
     * @var mixed|string
     */
    private $actionSuccessResponseKey;

    /**
     * @var mixed
     */
    private $transactionType;

    /**
     * @var Session
     */
    private $session;

    /**
     * PayoneerResponseHandler constructor.
     *
     * @param SubjectReader $subjectReader
     * @param Session $checkoutSession
     * @param string|mixed $additionalInfoKey
     * @param string|mixed $actionSuccessResponseKey
     * @param string $transactionType
     */
    public function __construct(
        SubjectReader $subjectReader,
        Session $checkoutSession,
        $additionalInfoKey = '',
        $actionSuccessResponseKey = '',
        $transactionType = ''
    ) {
        $this->subjectReader = $subjectReader;
        $this->session = $checkoutSession;
        $this->additionalInfoKey = $additionalInfoKey;
        $this->actionSuccessResponseKey = $actionSuccessResponseKey;
        $this->transactionType = $transactionType;
    }

    /**
     * Handle the response.
     *
     * @param array <mixed> $handlingSubject
     * @param array <mixed> $response
     * @return void
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        /** @var Payment $orderPayment */
        $orderPayment = $paymentDO->getPayment();

        if ($this->session->getFetchNotificationResponse()) {
            $additionalInfo = $this->session->getFetchNotificationResponse();
            $longId = $additionalInfo['long_id'];
            $orderPayment->setTransactionId($longId . '- refund');
        } else {
            $additionalInfo = $this->buildAdditionalInfoDataFromResponse($response);
            if ($this->transactionType == self::REFUND_TXN_TYPE) {
                $longId = $response['response']['identification']['longId'];
                $orderPayment->setTransactionId($longId . '- refund');
            }
        }
        $orderPayment->setAdditionalInformation(
            $this->additionalInfoKey,
            $additionalInfo
        );
        $orderPayment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $additionalInfo
        );
    }

    /**
     * Build the payment additional info data.
     *
     * @param array <mixed> $response
     * @return array <mixed>
     */
    public function buildAdditionalInfoDataFromResponse($response)
    {
        $additionalInfo = [
            'resultinfo' => $response['response']['resultInfo'],
            'returncode_name' => $response['response']['returnCode']['name'],
            'returncode_source' => $response['response']['returnCode']['source'],
            'status_code' => $response['response']['status']['code'],
            'status_reason' => $response['response']['status']['reason'],
            'interaction_code' => $response['response']['interaction']['code'],
            'interaction_reason' => $response['response']['interaction']['reason'],
            'longId' => $response['response']['identification']['longId'],
            'shortId' => $response['response']['identification']['shortId'],
            'transactionId' => $response['response']['identification']['transactionId'],
            'amount' => $response['response']['payment']['amount']
        ];
        if (!empty($this->actionSuccessResponseKey)) {
            $additionalInfo[$this->actionSuccessResponseKey] = 'success';
        }

        return $additionalInfo;
    }
}
