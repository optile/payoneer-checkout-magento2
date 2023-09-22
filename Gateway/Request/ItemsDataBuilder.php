<?php
namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Item;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Gateway\QuoteAdapter as PayoneerQuoteAdapter;
use Payoneer\OpenPaymentGateway\Model\Helper;

/**
 * Class ItemsDataBuilder
 * Build Item Data
 */
class ItemsDataBuilder implements BuilderInterface
{
    const SHIPPING_ADJUSTMENTS = 'Shipping Adjustments';
    const SHIPPING_ADJUSTMENTS_CODE = 'shipping_adjustments';
    const DISCOUNT_ADJUSTMENTS = 'Discount Adjustments';
    const DISCOUNT_ADJUSTMENTS_CODE = 'discount_adjustments';

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param Helper $helper
     */
    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * Builds items data
     *
     * @param array <mixed> $buildSubject
     * @return array <mixed>
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        $order = $payment->getOrder();

        $totalItemsCount = isset($buildSubject['totalItemsCount'])
            ? $buildSubject['totalItemsCount'] : 0;

        if ($order->getItems() && $totalItemsCount > 0) {
            $items = $this->buildItems($order);
            return [
                Config::PRODUCTS => $items
            ];
        } else {
            return [];
        }
    }

    /**
     * Build items
     *
     * @param OrderAdapterInterface $order
     * @return array <mixed>
     */
    protected function buildItems($order)
    {
        $result = [];
        /** @var Item[] $items */
        $items = $order->getItems();
        foreach ($items as $item) {
            $result[] = [
                Config::SKU             =>  $item->getSku(),
                Config::NAME            =>  $item->getName(),
                Config::QUANTITY        =>  $item->getData('qty'),
                Config::CURRENCY        =>  $order->getCurrencyCode(),
                Config::AMOUNT          =>  floatval($this->helper->formatNumber($item->getBaseRowTotalInclTax())),
                Config::NET_AMOUNT      =>  floatval($this->helper->formatNumber($item->getBaseRowTotal())),
                Config::TAX_AMOUNT      =>  floatval($this->helper->formatNumber($item->getBaseTaxAmount()))
            ];
        }
        if ($order instanceof PayoneerQuoteAdapter) {

            $shippingAmount = $order->getShippingAmountInclTax()?$this->helper->formatNumber($order->getShippingAmountInclTax()):'0.00';
            $shippingNetAmount = $order->getShippingAmount()?$this->helper->formatNumber($order->getShippingAmount()):'0.00';
            $shippingTaxAmount = $order->getOrderShippingTaxAmount()?$this->helper->formatNumber($order->getOrderShippingTaxAmount()):'0.00';

            $result[] = [
                Config::NAME        =>  self::SHIPPING_ADJUSTMENTS,
                Config::AMOUNT      =>  floatval($shippingAmount),
                Config::QUANTITY    =>  1,
                Config::CURRENCY    =>  $order->getCurrencyCode(),
                Config::NET_AMOUNT  =>  floatval($shippingNetAmount),
                Config::SKU         =>  self::SHIPPING_ADJUSTMENTS_CODE,
                Config::TAX_AMOUNT  =>  floatval($shippingTaxAmount)
            ];

            $orderDiscountAmount = $order->getDiscountAmount()?$this->helper->formatNumber($order->getDiscountAmount()):'0.00';

            $result[] = [
                Config::NAME        =>  self::DISCOUNT_ADJUSTMENTS,
                Config::AMOUNT      =>  floatval($orderDiscountAmount),
                Config::QUANTITY    =>  1,
                Config::CURRENCY    =>  $order->getCurrencyCode(),
                Config::NET_AMOUNT  =>  floatval($orderDiscountAmount),
                Config::SKU         =>  self::DISCOUNT_ADJUSTMENTS_CODE,
                Config::TAX_AMOUNT  =>  floatval('0.00')
            ];
        }

        return $result;
    }
}
