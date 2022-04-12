<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class RefundTransferFactory
 *
 * Builds refund transfer object
 */
class RefundTransferFactory extends TransferFactory
{
    /**
     * @inheritDoc
     */
    protected function getApiUri(PaymentDataObjectInterface $payment)
    {
        $payment = $payment->getPayment();
        $captureResponse = $payment->getAdditionalInformation('capture_response');
        if ($captureResponse) {
            $longId = $captureResponse['longId'];
        } else {
            $longId = $payment->getAdditionalInformation('longId');
        }

        return sprintf(
            Config::REFUND_END_POINT,
            $longId
        );
    }
}
