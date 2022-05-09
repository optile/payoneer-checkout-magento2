<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class ListUpdateTransferFactory
 *
 * Builds gateway transfer object
 */
class ListUpdateTransferFactory extends TransferFactory
{
    /**
     * @inheritDoc
     */
    protected function getApiUri(PaymentDataObjectInterface $payment)
    {
        $additionalInformation = $payment->getPayment()->getAdditionalInformation();
        $longId = $additionalInformation['longId'];

        return sprintf(
            Config::CAPTURE_END_POINT,
            $longId
        );
    }
}
