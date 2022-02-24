<?php

namespace Payoneer\OpenPaymentGateway\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Disable
 * Disables the configuration field
 */
class Disable extends Field
{
    /**
     * Disables the configuration field
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setDisabled('disabled');
        return $element->getElementHtml();

    }
}
