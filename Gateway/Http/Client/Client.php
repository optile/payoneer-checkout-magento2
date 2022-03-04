<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http\Client;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Api\Request;

/**
 * Class Client
 * Payoneer client for transactions
 */
class Client implements ClientInterface
{
    const AUTHORIZE = 'authorize';
    const LIST      = 'list';
    const CAPTURE   = 'authorize_capture';

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
     * @param Logger $logger
     * @param Request $request
     * @param Config $config
     * @param $operation
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
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [];

        switch ($this->operation) {
            case self::LIST:
                $responseObj = $this->processListRequest($transferObject);
                break;
            case self::AUTHORIZE:
            case self::CAPTURE:
                $responseObj = $this->processAuthRequest($transferObject);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Unknown operation [%s]', $this->operation));
        }

        $response['response'] = $responseObj->getData('response') ?: '';
        $response['status'] = $responseObj->getData('status') ?: '';
        $response['reason'] = $responseObj->getData('reason') ?: '';
        if ((bool)$this->config->getValue('debug') == true) {
            $this->logger->debug(['response' => $response]);
        }

        return $response;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|DataObject
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
            Config::END_POINT,
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
        $responseObject->setData('response', $this->getResultCode($transferObject));
        return $responseObject;
    }

    /**
     * @param TransferInterface $transfer
     * @return array|mixed
     */
    private function getResultCode(TransferInterface $transfer)
    {
        $headers = $transfer->getHeaders();

        if (isset($headers[Config::TXN_ID])) {
            return [Config::TXN_ID => $headers[Config::TXN_ID]];
        }

        return [];
    }
}
