<?php

namespace Payoneer\OpenPaymentGateway\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;
use Payoneer\OpenPaymentGateway\Gateway\Response\FraudHandler;

/**
 * Class Info
 *
 * Info block for Payoneer payment gateway
 */
class Info extends ConfigurableInfo
{
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     */
    protected function getLabel($field)
    {
        return __($field);
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     */
    protected function getValueView($field, $value)
    {
        switch ($field) {
            case 'test'://FraudHandler::FRAUD_MSG_LIST: //todo
                return implode('; ', $value);
        }
        return parent::getValueView($field, $value);
    }
}
