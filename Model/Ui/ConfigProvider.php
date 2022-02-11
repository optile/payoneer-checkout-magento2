<?php

namespace Payoneer\OpenPaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Http\Client\Client;

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
                        Client::SUCCESS => __('Success'),
                        Client::FAILURE => __('Fraud')
                    ]
                ]
            ]
        ];
    }
}
