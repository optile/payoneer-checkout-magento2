<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Response;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use PayPal\Braintree\Gateway\Helper\SubjectReader;
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
     * PayoneerResponseHandler constructor.
     *
     * @param SubjectReader $subjectReader
     * @param string|mixed $additionalInfoKey
     * @param string|mixed $actionSuccessResponseKey
     * @return void
     */
    public function __construct(
        SubjectReader $subjectReader,
        $additionalInfoKey = '',
        $actionSuccessResponseKey = ''
    ) {
        $this->subjectReader = $subjectReader;
        $this->additionalInfoKey = $additionalInfoKey;
        $this->actionSuccessResponseKey = $actionSuccessResponseKey;
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

        $additionalInfo = $this->buildAdditionalInfoDataFromResponse($response);
        $orderPayment->setAdditionalInformation(
            $this->additionalInfoKey,
            $additionalInfo
        );
        $orderPayment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $additionalInfo /** @phpstan-ignore-line */
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
            'transactionId' => $response['response']['identification']['transactionId']
        ];
        if (!empty($this->actionSuccessResponseKey)) {
            $additionalInfo[$this->actionSuccessResponseKey] = 'success';
        }

        return $additionalInfo;
    }
}
