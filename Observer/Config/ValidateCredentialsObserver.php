<?php

namespace Payoneer\OpenPaymentGateway\Observer\Config;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config as PayoneerConfig;
use Payoneer\OpenPaymentGateway\Model\Api\Request;

/**
 * Class ValidateCredentials - Validates the API credentials in admin
 *
 */
class ValidateCredentialsObserver implements ObserverInterface
{
    /**
     * @var PayoneerConfig
     */
    private $payoneerConfig;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var array <mixed>
     */
    private $configFieldValues = [];

    /**
     * ValidateCredentialsObserver constructor.
     * @param PayoneerConfig $payoneerConfig
     * @param Context $context
     * @param Request $request
     */
    public function __construct(
        PayoneerConfig $payoneerConfig,
        Context $context,
        Request $request
    ) {
        $this->payoneerConfig = $payoneerConfig;
        $this->context = $context;
        $this->request = $request;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        $this->validateApi($observer);
    }

    /**
     * Validates Api call
     * @param Observer $observer
     * @throws AlreadyExistsException
     * @return void
     */
    public function validateApi(Observer $observer)
    {
        if (!$this->payoneerConfig->getValue('active')) {
            return;
        }

        $credentials = [];
        $sharedFields = ['environment', 'merchant_gateway_key'];
        $sandboxFields = ['sandbox_api_key', 'sandbox_host_name', 'sandbox_store_code'];
        $liveFields = ['live_api_key', 'live_host_name', 'live_store_code'];
        /** @phpstan-ignore-next-line */
        $groups = $this->context->getRequest()->getPost('groups');
        $payoneerGroup = $groups['payoneer'];

        $this->prepareConfigValues($sharedFields, $payoneerGroup);

        if ($this->configFieldValues['environment'] == 'test') {
            $this->prepareConfigValues($sandboxFields, $payoneerGroup);
            $apiKey = $this->configFieldValues['sandbox_api_key'];
            $hostName = $this->configFieldValues['sandbox_host_name'];
            $storeCode = $this->configFieldValues['sandbox_store_code'];
        } else {
            $this->prepareConfigValues($liveFields, $payoneerGroup);
            $apiKey = $this->configFieldValues['live_api_key'];
            $hostName = $this->configFieldValues['live_host_name'];
            $storeCode = $this->configFieldValues['live_store_code'];
        }

        $credentials['merchantCode'] = $this->configFieldValues['merchant_gateway_key'];
        $credentials['apiKey'] = $apiKey;
        $credentials['hostName'] = $hostName;

        $data = $this->payoneerConfig->getMockData();
        if ($storeCode) {
            $data['division'] = $storeCode;
        }

        $response = $this->request->send(
            PayoneerConfig::METHOD_POST,
            PayoneerConfig::LIST_END_POINT,
            $credentials,
            $data
        );

        if ($response->getData('status') !== 200) {
            throw new AlreadyExistsException(__(
                'Payoneer validation failed. Make sure the credentials you have entered are correct.'
            ));
        }
    }

    /**
     * Prepare config values
     * @param array <mixed> $fields
     * @param array <mixed> $group
     * @return void
     */
    public function prepareConfigValues($fields, $group)
    {
        foreach ($fields as $field) {
            if (isset($group['fields'][$field]['inherit']) || !isset($group['fields'][$field]['value'])) {
                $this->configFieldValues[$field] = $this->payoneerConfig->getValue($field);
            } else {
                $this->configFieldValues[$field] = $group['fields'][$field]['value'];
            }
        }
    }
}