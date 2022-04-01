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

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config,
        Helper $helper
    ) {
        $this->config = $config;
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
                Config::AMOUNT          =>  $this->helper->formatNumber($item->getBaseRowTotal()),
                Config::NET_AMOUNT      =>  $this->helper->formatNumber($item->getBaseRowTotal()),
                Config::TAX_AMOUNT      =>  $this->helper->formatNumber($item->getBaseTaxAmount()),
                Config::TAX_PERCENT     =>  $this->helper->formatNumber($item->getBaseRowTotalInclTax())
            ];
        }
        if ($order instanceof PayoneerQuoteAdapter) {
            $totalAdjustments = 0.00;
            if ($order->getShippingAmountInclTax() > 0) {
                $totalAdjustments += $order->getShippingAmountInclTax();
            }
            if ($order->getDiscountAmount() < 0) {
                $totalAdjustments += $order->getDiscountAmount();
            }
            if ($order->getTaxAmount() > 0) {
                $totalAdjustments += $order->getTaxAmount();
            }
            $result[] = [
                Config::NAME => self::ADJUSTMENTS,
                Config::AMOUNT => number_format($totalAdjustments, 2)
            ];

        }

        return $result;
    }
}
