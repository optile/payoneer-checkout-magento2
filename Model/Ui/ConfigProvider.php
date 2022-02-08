<?php

namespace Payoneer\OpenPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider - Payoneer configuration class
 */
class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'payoneer';

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}
