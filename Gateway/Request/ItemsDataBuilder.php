<?php
namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Sales\Model\Order\Item;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Gateway\QuoteAdapter as PayoneerQuoteAdapter;

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
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
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
                Config::AMOUNT          =>  number_format($item->getBaseRowTotal(), 2),
                Config::NET_AMOUNT      =>  number_format($item->getBaseRowTotal(), 2),
                Config::TAX_AMOUNT      =>  number_format($item->getBaseTaxAmount(), 2),
                Config::TAX_PERCENT     =>  number_format($item->getBaseRowTotalInclTax(), 2)
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
            if ($totalAdjustments > 0) {
                $result[] = [
                    Config::NAME => self::ADJUSTMENTS,
                    Config::AMOUNT => number_format($totalAdjustments, 2)
                ];
            }
        }

        return $result;
    }
}
