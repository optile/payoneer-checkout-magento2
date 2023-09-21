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
    const ADJUSTMENTS = 'Total Adjustments';
    const ADJUSTMENTS_CODE = 'total_adjustments';

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
            $paymentNetAmount = 0.00;
            if ($items) {
                foreach ($items as $item) {
                    $paymentNetAmount += $item[Config::NET_AMOUNT];
                }
            }
            return [
                Config::PRODUCTS => $items,
                Config::PAYMENT => [
                    Config::NET_AMOUNT => $paymentNetAmount
                ]
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
            $totalAdjustments = 0.00;
            $netTotalAdjustments = 0.00;
            $shippingAmountWithTax = 0.00;
            if ($order->getShippingAmountInclTax() > 0) {
                $totalAdjustments += $order->getShippingAmountInclTax();
                $shippingAmountWithTax = $totalAdjustments;
            }
            if ($order->getShippingAmount() > 0) {
                $netTotalAdjustments += $order->getShippingAmount();
            }
            if ($order->getDiscountAmount() < 0) {
                $totalAdjustments += $order->getDiscountAmount();
            }

            $adjustmentTaxAmount = $shippingAmountWithTax - $netTotalAdjustments;

            $result[] = [
                Config::NAME        =>  self::ADJUSTMENTS,
                Config::AMOUNT      =>  floatval(number_format($totalAdjustments, 2)),
                Config::QUANTITY    =>  1,
                Config::CURRENCY    =>  $order->getCurrencyCode(),
                Config::NET_AMOUNT  =>  floatval($netTotalAdjustments?$this->helper->formatNumber($netTotalAdjustments):'0.00'),
                Config::SKU         =>  self::ADJUSTMENTS_CODE,
                Config::TAX_AMOUNT  =>  floatval($adjustmentTaxAmount?$this->helper->formatNumber($adjustmentTaxAmount):'0.00')
            ];
        }

        return $result;
    }
}
