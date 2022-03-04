<?php

namespace Payoneer\OpenPaymentGateway\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Fields
 * Admin config fields
 */
class Fields implements OptionSourceInterface
{
    const ENVIRONMENT_PRODUCTION_LABEL  =   'Live';
    const ENVIRONMENT_PRODUCTION_VALUE  =   'live';
    const ENVIRONMENT_SANDBOX_LABEL     =   'Test';
    const ENVIRONMENT_SANDBOX_VALUE     =   'test';
    const HOSTED                        =   'HOSTED';
    const SELECTIVE_NATIVE              =   'SELECTIVE_NATIVE';
    const AUTHORIZE                     =   'authorize';
    const CAPTURE                       =   'authorize_capture';
    const STANDALONE                    =   'Standalone';
    const EMBEDDED                      =   'Embedded';
    const DIRECT_PAYMENT                =   'Direct payment';
    const DEFERRED                      =   'Deferred';
    const FONT_BOLD                     =   'bold';
    const FONT_LIGHTER                  =   'lighter';
    const FONT_NORMAL                   =   'normal';
    const REGULAR                       =   'Regular';
    const BOLD                          =   'Bold';
    const LIGHTER                       =   'Lighter';
    const LEFT                          =   'Left';
    const ALIGN_LEFT                    =   'left';
    const RIGHT                         =   'Right';
    const ALIGN_RIGHT                   =   'right';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ENVIRONMENT_SANDBOX_VALUE,
                'label' => self::ENVIRONMENT_SANDBOX_LABEL
            ],
            [
                'value' => self::ENVIRONMENT_PRODUCTION_VALUE,
                'label' => self::ENVIRONMENT_PRODUCTION_LABEL
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
                'value' => self::FONT_LIGHTER,
                'label' => self::LIGHTER,
            ],
            [
                'value' => self::FONT_NORMAL,
                'label' => self::REGULAR
            ],
            [
                'value' => self::FONT_BOLD,
                'label' => self::BOLD
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
                'value' => self::ALIGN_LEFT,
                'label' => self::LEFT,
            ],
            [
                'value' => self::ALIGN_RIGHT,
                'label' => self::RIGHT
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
                'value' => self::AUTHORIZE,
                'label' => self::DEFERRED
            ],
            [
                'value' => self::CAPTURE,
                'label' => self::DIRECT_PAYMENT
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
                'value' => self::HOSTED,
                'label' => self::STANDALONE
            ],
            [
                'value' => self::SELECTIVE_NATIVE,
                'label' => self::EMBEDDED
            ]
        ];
    }
}
