<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Source\Fields;

/**
 * Class PreselectionBuilder
 * Builds 'preselection' array
 */
class PreselectionBuilder implements BuilderInterface
{
    /**
     * Preference array constants
     */
    const DEFERRED = 'DEFERRED';
    const NON_DEFERRED = 'NON_DEFERRED';

    /**
     * @var Config
     */
    protected $config;

    /**
     * PreselectionBuilder constructor.
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds preselection data
     *
     * @param array <mixed> $buildSubject
     * @return array <mixed>
     */
    public function build(array $buildSubject)
    {
        return [
            Config::PRESELECTION => [
                Config::DEFERRAL => $this->config->getValue('payment_action') == Fields::AUTHORIZE
                    ? self::DEFERRED
                    : self::NON_DEFERRED
            ]
        ];
    }
}
