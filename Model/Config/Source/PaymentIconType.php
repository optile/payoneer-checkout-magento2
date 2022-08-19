<?php

namespace Payoneer\OpenPaymentGateway\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PaymentIconType implements OptionSourceInterface
{
    const PAYMENT_ICON_STATIC   = 'static';
    const PAYMENT_ICON_DYNAMIC  = 'dynamic';
    const PAYMENT_ICON_BOTH     = 'both';

    /**
     * Payment icon types
     *
     * @return array <mixed>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::PAYMENT_ICON_STATIC,
                'label' => __('Static')
            ],
            [
                'value' => self::PAYMENT_ICON_DYNAMIC,
                'label' => __('Dynamic')
            ],
            [
                'value' => self::PAYMENT_ICON_BOTH,
                'label' => __('Both')
            ]
        ];
    }
}
