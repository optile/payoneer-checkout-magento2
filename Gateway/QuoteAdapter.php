<?php
namespace Payoneer\OpenPaymentGateway\Gateway;

use Magento\Payment\Gateway\Data\Quote\AddressAdapterFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Payment\Gateway\Data\Quote\QuoteAdapter as MagentoQuoteAdapter;

/**
 * Class QuoteAdapter
 * Gets the details from quote
 */
class QuoteAdapter extends MagentoQuoteAdapter
{
    /** @var Quote */
    protected $quote;

    /**
     * QuoteAdapter constructor.
     * @param CartInterface $quote
     * @param AddressAdapterFactory $addressAdapterFactory
     */
    public function __construct(CartInterface $quote, AddressAdapterFactory $addressAdapterFactory)
    {
        parent::__construct($quote, $addressAdapterFactory);
        $this->quote = $quote;
    }

    /**
     * Get remote ip
     * @return string|null
     */
    public function getRemoteIp()
    {
        return $this->quote->getRemoteIp();
    }

    /**
     * Get shipping amount
     * @return float
     */
    public function getShippingAmount()
    {
        return $this->getAddressModel()->getBaseShippingAmount();
    }

    /**
     * @return Address
     */
    protected function getAddressModel()
    {
        return $this->quote->isVirtual() ? $this->quote->getBillingAddress() : $this->quote->getShippingAddress();
    }

    /**
     * Get quote items
     * @return array|CartItemInterface[]|null
     */
    public function getItems()
    {
        $items = parent::getItems();
        if (!$items) {
            $items = $this->quote->getAllItems();
        }

        return $items;
    }
}