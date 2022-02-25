<?php

namespace Payoneer\OpenPaymentGateway\Block;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

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
    /*protected function getValueView($field, $value)
    {
        switch ($field) {
            case 'FRAUD_MSG_LIST'://FraudHandler::FRAUD_MSG_LIST: //todo
                return implode('; ', $value);
        }
        return parent::getValueView($field, $value);
    }*/
}
