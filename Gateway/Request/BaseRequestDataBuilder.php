<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Request;

use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Source\Fields;

/**
 * Class BaseRequestDataBuilder
 * Builds base request data
 */
class BaseRequestDataBuilder implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @param Config $config
     * @param Request $request
     */
    public function __construct(
        Config $config,
        Request $request,
        ModuleListInterface $moduleList
    ) {
        $this->config = $config;
        $this->request = $request;
        $this->moduleList = $moduleList;
    }

    /**
     * Builds base request data
     *
     * @param array <mixed> $buildSubject
     * @return array <mixed>
     */
    public function build(array $buildSubject)
    {
        $payment = SubjectReader::readPayment($buildSubject);

        return [
            Config::TRANSACTION_ID  => $payment->getPayment()->getAdditionalInformation(Config::TXN_ID),
            Config::COUNTRY         => $this->getCountryId($payment, $buildSubject),
            Config::INTEGRATION     => $this->getPaymentFlow(),
            Config::DIVISION        => $this->config->getValue('environment') == Fields::ENVIRONMENT_SANDBOX_VALUE
                ? $this->config->getValue('sandbox_store_code')
                : $this->config->getValue('live_store_code'),
            Config::ALLOW_DELETE    => true,

            //system attributes
            "system"                => [
                "type"              => "SHOP_PLATFORM",
                "code"              => "MAGENTO",
                "version"           => $this->moduleList->getOne('Payoneer_OpenPaymentGateway')['setup_version']
            ]
        ];
    }

    /**
     * @param PaymentDataObjectInterface $payment
     * @return string
     */
    private function getCountryId($payment, $buildSubject)
    {

        //use country of shipping address if it exists, else billing address or store country
        $shipAddress = $buildSubject['shipAddress'] ?? null;
        if ($shipAddress && $shipAddress[Config::COUNTRY_ID]) {
            return $shipAddress[Config::COUNTRY_ID];
        }

        $order = $payment->getOrder();

        $shippingAddress = $order->getShippingAddress();
        if (isset($shippingAddress) && $shippingAddress->getCountryId()) {
            return $shippingAddress->getCountryId();
        } else {
            $billingAddress = $buildSubject['address'] ?? null;
            if($billingAddress && $billingAddress[Config::COUNTRY_ID]) {
                return $billingAddress[Config::COUNTRY_ID];
            }
            $billingAddress = $order->getBillingAddress();
            if (isset($billingAddress) && $billingAddress->getCountryId()) {
                return $billingAddress->getCountryId();
            } else {
                return $this->config->getCountryByStore();
            }
        }

        return null;
    }

    /**
     * Build payment flow from request
     *
     * @return string
     */
    private function getPaymentFlow()
    {
        $integration = $this->request->getParam(Config::INTEGRATION);
        if ($integration == Config::INTEGRATION_HOSTED) {
            return Config::HOSTED;
        }
        if ($integration == Config::INTEGRATION_EMBEDDED) {
            return Config::SELECT_NATIVE;
        }
        return $this->config->getValue(Config::PAYMENT_FLOW);
    }
}
