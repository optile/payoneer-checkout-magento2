<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class TransactionIDDataBuilder
 * Builds magento transaction request
 */
class TransactionIDDataBuilder implements BuilderInterface
{
    /**
     * Builds transaction id
     *
     * @param array <mixed> $buildSubject
     * @return array <mixed>
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        return [
            Config::TXN_ID => $payment->getPayment()->getAdditionalInformation(Config::TXN_ID)
        ];
    }
}
