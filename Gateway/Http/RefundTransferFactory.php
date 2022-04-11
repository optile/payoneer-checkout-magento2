<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

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
        $captureResponse = $payment->getPayment()->getAdditionalInformation('capture_response');
        $longId = $captureResponse['longId'];

        return sprintf(
            Config::REFUND_END_POINT,
            $longId
        );
    }
}
