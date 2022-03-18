<?php

namespace Payoneer\OpenPaymentGateway\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\Store;

/**
 * Class ValidateCredentials
 * Creates button for validating the credentials
 */
class ValidateCredentials extends Field
{
    /**
     * @inheritDoc
     */
    protected function _renderScopeLabel(AbstractElement $element): string
    {
        // Return empty label
        return '';
    }

    /**
     * @inheritDoc
     * @throws LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        // Replace field markup with validation button
        $title = __('Validate Credentials');
        $envId = 'select-groups-payoneer-fields-environment-value';
        $storeId = 0;

        if ($this->getRequest()->getParam('website')) {
            $website = $this->_storeManager->getWebsite($this->getRequest()->getParam('website'));
            if ($website->getId()) {
                /** @var Store $store */
                $store = $website->getDefaultStore();/** @phpstan-ignore-line */
                $storeId = $store->getStoreId();
            }
        }

        $endpoint = $this->getUrl('payoneer/configuration/validatecredentials', ['storeId' => $storeId]);

        // @codingStandardsIgnoreStart
        $html = <<<TEXT
            <button
                type="button"
                title="{$title}"
                class="button"
                onclick="payoneerValidator.call(this, '{$endpoint}', '{$envId}')">
                <span>{$title}</span>
            </button>
TEXT;
        // @codingStandardsIgnoreEnd

        return $html;
    }
}
