<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class StyleDataBuilder
 * Builds Style data array
 */
class StyleDataBuilder implements BuilderInterface
{
    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        return [
            Config::STYLE => [
                Config::HOSTED_VERSION => 'v4',
                /*'resolution' => '3x',*/ //to do on embedded integration
            ]
        ];
    }
}
