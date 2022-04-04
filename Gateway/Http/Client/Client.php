<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http\Client;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Api\Request;
use Payoneer\OpenPaymentGateway\Model\Method\Logger;

/**
 * Class Client
 * Payoneer client for transactions
 */
class Client implements ClientInterface
{
    const AUTHORIZE     = 'authorize';
    const LIST          = 'list';
    const CAPTURE       = 'authorize_capture';
    const LIST_CAPTURE  = 'list_capture';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var string
     */
    protected $operation;

    /**
     * Mandatory request fields
     * @var string[]
     */
    protected $mandatoryFields = [
        'transactionId',
        'country',
        'customer',
        'payment',
        'callback'
    ];

    /**
     * Mandatory request fields for 'payment' object
     * @var string[]
     */
    protected $mandatoryFieldsPayment = [
        'amount',
        'currency',
        'reference',
        'invoiceId'
    ];

    /**
     * Mandatory request fields for 'customer' object
     * @var string[]
     */
    protected $mandatoryFieldsCustomer = [
        'number'
    ];

    /**
     * @var array <mixed> | string
     */
    protected $requestData;

    /**
     * @param Logger $logger
     * @param Request $request
     * @param Config $config
     * @param string $operation
     */
    public function __construct(
        Logger $logger,
        Request $request,
        Config $config,
        $operation
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->config = $config;
        $this->operation = $operation;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|DataObject
     * @return array <mixed>
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [];
        $responseObj = null;
        $this->requestData = $transferObject->getBody();
        $this->logData(['operation' => $this->operation]);
        switch ($this->operation) {
            case self::LIST:
                $isRequestValid = $this->validateRequest();
                if ($isRequestValid) {
                    $responseObj = $this->processListRequest($transferObject);
                } else {
                    $this->logData(['request' => $this->requestData]);
                }
                break;
            case self::AUTHORIZE:
            case self::CAPTURE:
                $responseObj = $this->processAuthRequest($transferObject);
                break;
            case self::LIST_CAPTURE:
                $responseObj = $this->processListRequest($transferObject);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown operation [%s]', $this->operation));
        }

        if ($responseObj instanceof DataObject) {
            $response['response'] = $responseObj->getData('response') ?: '';
            $response['status'] = $responseObj->getData('status') ?: '';
            $response['reason'] = $responseObj->getData('reason') ?: '';
        }

        $this->logData($response);

        return $response;
    }

    /**
     * @param TransferInterface $transferObject
     * @return DataObject <mixed> | DataObject
     */
    public function processListRequest($transferObject)
    {
        $credentials['merchantCode'] = $this->config->getValue('merchant_gateway_key');
        $credentials['apiKey'] = $this->config->getCredentials('api_key');
        $credentials['hostName'] = $this->config->getCredentials('host_name');

        $data = $transferObject->getBody();

        if ((bool)$this->config->getValue('debug') == true) {
            $this->logger->debug(['request' => $data]);
        }
        return $this->request->send(
            $transferObject->getMethod(),
            $transferObject->getUri(),
            $credentials,
            $data
        );
    }

    /**
     * @param TransferInterface $transferObject
     * @return DataObject
     */
    protected function processAuthRequest($transferObject)
    {
        $responseObject = new \Magento\Framework\DataObject();
        $responseObject->setData('response', $this->getResponseData($transferObject));
        return $responseObject;
    }

    /**
     * @param TransferInterface $transfer
     * @return array|mixed
     */
    private function getResponseData(TransferInterface $transfer)
    {
        $headers = $transfer->getHeaders();

        if (isset($headers[Config::TXN_ID])) {
            return [Config::TXN_ID => $headers[Config::TXN_ID]];
        }

        return [];
    }

    /**
     * Validate payoneer request data
     * @return bool
     */
    public function validateRequest()
    {
        $isValid = true;
        foreach ($this->mandatoryFields as $mandatoryField) {
            switch ($mandatoryField) {
                case 'payment':
                    $isValid = $this->mandatoryFieldsExists($this->mandatoryFieldsPayment, 'payment');
                    break;
                case 'customer':
                    $isValid = $this->mandatoryFieldsExists($this->mandatoryFieldsCustomer, 'customer');
                    break;
                default:
                    if (is_array($this->requestData) && !isset($this->requestData[$mandatoryField]) ||
                        (is_array($this->requestData)
                            && isset($this->requestData[$mandatoryField])
                            && $this->requestData[$mandatoryField] == '')) {
                        $this->logData([$mandatoryField . ' must not be empty']);
                        return false;
                    }
            }
            if (!$isValid) {
                return false;
            }
        }
        return $isValid;
    }

    /**
     * Check if mandatory fields exists
     * @param array <mixed> $mandatoryFields
     * @param string $objectName
     * @return bool
     */
    public function mandatoryFieldsExists($mandatoryFields, $objectName)
    {
        foreach ($mandatoryFields as $mandatoryField) {
            if (is_array($this->requestData) && !isset($this->requestData[$objectName][$mandatoryField])) {
                $this->logData([$objectName . '.' . $mandatoryField . ' must not be empty']);
                return false;
            }
        }
        return true;
    }

    /**
     * Log data to payoneer.log
     * @param array <mixed> $result
     * @return void
     */
    public function logData($result)
    {
        if ((bool)$this->config->getValue('debug') == true) {
            $this->logger->debug([$result]);
        }
    }
}
