<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http\Authorization;

use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Payoneer\OpenPaymentGateway\Gateway\Http\TransferFactory;

/**
 * Class CancellationTransferFactory
 *
 * Builds authorization cancellation transfer object
 */
class CancellationTransferFactory extends TransferFactory
{
    /**
     * @inheritDoc
     */
    protected function getApiUri(PaymentDataObjectInterface $payment)
    {
        $longId = $payment->getPayment()->getAdditionalInformation('longId');

        return sprintf(
            Config::AUTHORIZATION_CANCEL_END_POINT,
            $longId
        );
    }

    /**
     * @inheritDoc
     */
    protected function getMethod()
    {
        return Config::METHOD_DELETE;
    }
}
