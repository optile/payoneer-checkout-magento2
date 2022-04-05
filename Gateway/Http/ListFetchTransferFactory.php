<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class ListFetchTransferFactory
 *
 * Builds gateway transfer object
 */
class ListFetchTransferFactory extends TransferFactory
{
    /**
     * @inheritDoc
     */
    protected function getApiUri(PaymentDataObjectInterface $payment)
    {
        $additionalInformation = $payment->getPayment()->getAdditionalInformation();
        $longId = $additionalInformation['longId'];

        return 'api/charges/' . $longId;
    }

    /**
     * Return the method
     *
     * @return string
     */
    protected function getMethod()
    {
        return Config::METHOD_GET;
    }
}
