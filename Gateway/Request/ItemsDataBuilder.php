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
    const SHIPPING = 'Shipping charge';

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
                Config::AMOUNT          =>  $item->getBasePrice(),
                Config::NET_AMOUNT      =>  $item->getBaseRowTotalInclTax(),
                Config::TAX_AMOUNT      =>  $item->getBaseTaxAmount(),
                Config::TAX_PERCENT     =>  $item->getBaseRowTotalInclTax()
            ];
        }
        if ($order instanceof PayoneerQuoteAdapter) {
            if ($order->getShippingAmount() > 0) {
                $result[] = [
                    Config::NAME    =>  self::SHIPPING,
                    Config::AMOUNT  =>  $order->getShippingAmount()
                ];
            }
        }
        return $result;
    }
}
