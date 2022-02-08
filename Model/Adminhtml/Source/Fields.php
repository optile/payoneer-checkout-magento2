<?php

namespace Payoneer\OpenPaymentGateway\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Environment - Lists the environment options
 */
class Fields implements OptionSourceInterface
{

    const ENVIRONMENT_PRODUCTION = 'live';
    const ENVIRONMENT_SANDBOX = 'test';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ENVIRONMENT_SANDBOX,
                'label' => 'Test',
            ],
            [
                'value' => self::ENVIRONMENT_PRODUCTION,
                'label' => 'Live'
            ]
        ];
    }

    /**
     * @return array|\string[][]
     */
    public function fontWeight(): array
    {
        return [
            [
                'value' => 'lighter',
                'label' => 'Lighter',
            ],
            [
                'value' => 'regular',
                'label' => 'Regular'
            ],
            [
                'value' => 'bold',
                'label' => 'Bold'
            ]
        ];
    }

    /**
     * @return array|\string[][]
     */
    public function alignText(): array
    {
        return [
            [
                'value' => 'left',
                'label' => 'Left',
            ],
            [
                'value' => 'right',
                'label' => 'Right'
            ]
        ];
    }

    /**
     * @return array|array[]
     */
    public function paymentAction(): array
    {
        return [
            [
                'value' => __('AUTH'),
                'label' => __('Deferred')
            ],
            [
                'value' => __('Deferred'),
                'label' => __('Direct payment')
            ]
        ];
    }

    /**
     * @return array|array[]
     */
    public function paymentFlow(): array
    {
        return [
            [
                'value' => __('standalone'),
                'label' => __('Standalone')
            ],
            [
                'value' => __('embedded'),
                'label' => __('Embedded')
            ]
        ];
    }
}
