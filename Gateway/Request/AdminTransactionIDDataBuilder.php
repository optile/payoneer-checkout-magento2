<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class AdminTransactionIDDataBuilder
 * Builds transaction request
 */
class AdminTransactionIDDataBuilder implements BuilderInterface
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
            Config::TRANSACTION_ID => $payment->getOrder()->getId() . strtotime('now')
        ];
    }
}
