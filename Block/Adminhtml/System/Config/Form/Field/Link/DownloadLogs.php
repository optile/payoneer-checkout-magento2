<?php

namespace Payoneer\OpenPaymentGateway\Block\Adminhtml\System\Config\Form\Field\Link;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Frontend model for log download button for Payoneer
 */
class DownloadLogs extends Field
{
    /**
     * Hides the store view label present on the left side of the button
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->setData('scope', null);
        $element->setData('can_use_website_value', null);
        $element->setData('can_use_default_value', null);
        return parent::render($element);
    }

    /**
     * Get download button
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return sprintf(
            '<a href="%s"  style="display: block;padding-top: 12px;">%s</a>',
            rtrim($this->_urlBuilder->getUrl('payoneer/downloadlogs/download')),
            __('Download Logs')
        );
    }
}
