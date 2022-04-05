<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class ListCaptureTransferFactory
 *
 * Builds gateway transfer object
 */
class ListCaptureTransferFactory extends TransferFactory
{
    /**
     * @inheritDoc
     */
    protected function getApiUri(PaymentDataObjectInterface $payment)
    {
        $additionalInformation = $payment->getPayment()->getAdditionalInformation();
        $longId = $additionalInformation['longId'];

        return 'api/charges/' . $longId . '/closing';
    }
}
