<?php
namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Item;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class ItemsDataBuilder
 * Build Item Data
 */
class ItemsDataBuilder implements BuilderInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        $order = $payment->getOrder();

        if ($order->getItems()) {
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
     * @param $order OrderAdapterInterface
     * @return array
     */
    protected function buildItems($order) // @codingStandardsIgnoreLine
    {
        $result = [];
        $itemsTotal = 0;
        /** @var Item[] $items */
        $items = $order->getItems();
        foreach ($items as $item) {
            $result[] = [
                Config::SKU         => $item->getSku(),
                Config::NAME => $item->getName(),
                Config::QUANTITY    => $item->getData('qty'),
                Config::CURRENCY    => $order->getCurrencyCode(),
                /*Config::TYPE    => $item->getProductType(),*/
                Config::AMOUNT   => $item->getBasePrice(),
                Config::NET_AMOUNT   => $item->getBaseRowTotalInclTax(),
                Config::TAX_AMOUNT         => $item->getBaseTaxAmount(),
                Config::TAX_PERCENT       => $item->getBaseRowTotalInclTax()
            ];
            $itemsTotal += $item->getBaseRowTotalInclTax();
        }

        /*if ($order instanceof \Eway\EwayRapid\Gateway\QuoteAdapter) {
            if ($order->getShippingAmount() > 0) {
                $result[] = [
                    Config::DESCRIPTION => 'Shipping',
                    Config::QUANTITY    => 1,
                    Config::UNIT_COST   => (int) round(100 * $order->getShippingAmount()),
                    Config::TAX         => (int) round(100 * $order->getShippingTaxAmount()),
                    Config::TOTAL       => (int) round(100 * $order->getShippingAmountInclTax())
                ];
                $itemsTotal += $order->getShippingAmountInclTax();
            }

            if ($order->getDiscountAmount() < 0) {
                $result[] = [
                    Config::DESCRIPTION => 'Discount',
                    Config::QUANTITY    => 1,
                    Config::UNIT_COST   => (int) round(100 * $order->getDiscountAmount()),
                    Config::TOTAL       => (int) round(100 * $order->getDiscountAmount())
                ];
                $itemsTotal += $order->getDiscountAmount();
            }
        }*/

        // Make sure the items total always match amount.
        /*if ($itemsTotal != $amount) {
            $adjustment = (int) round(100 * ($amount - $itemsTotal));
            $result[] = [
                Config::DESCRIPTION => 'Adjustment',
                Config::QUANTITY    => 1,
                Config::UNIT_COST   => $adjustment,
                Config::TOTAL       => $adjustment
            ];
        }*/

        return $result;
    }
}
