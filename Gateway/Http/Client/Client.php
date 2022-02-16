<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http\Client;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Api\Request;

class Client implements ClientInterface
{
    const SUCCESS = 1;
    const FAILURE = 0;

    /**
     * @var array
     */
    private $results = [
        self::SUCCESS,
        self::FAILURE
    ];

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
     * @param Logger $logger
     * @param Request $request
     * @param Config $config
     */
    public function __construct(
        Logger $logger,
        Request $request,
        Config $config
    ) {
        $this->logger = $logger;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|DataObject
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [];

        $credentials['merchantCode'] = $this->config->getValue('merchant_gateway_key');
        $credentials['apiKey'] = $this->config->getCredentials('api_key');
        $credentials['hostName'] = $this->config->getCredentials('host_name');

        $data = $transferObject->getBody();
        if ((bool)$this->config->getValue('debug') == true) {
            $this->logger->debug(['request' => $data]);
        }

        $responseObj = $this->request->send(
            $transferObject->getMethod(),
            Config::END_POINT,
            $credentials,
            $data
        );

        $response['response'] = $responseObj->getData('response');
        $response['status'] = $responseObj->getData('status');
        $response['reason'] = $responseObj->getData('reason');

        if ((bool)$this->config->getValue('debug') == true) {
            $this->logger->debug(['response' => $response]);
        }

        return $response;
    }

    /**
     * Generates response
     *
     * @return array
     */
    protected function generateResponseForCode($resultCode)
    {
        return array_merge(
            [
                'RESULT_CODE' => $resultCode,
                'TXN_ID' => $this->generateTxnId()
            ],
            $this->getFieldsBasedOnResponseType($resultCode)
        );
    }

    /**
     * @return string
     */
    protected function generateTxnId()
    {
        return md5(mt_rand(0, 1000));
    }

    /**
     * Returns result code
     *
     * @param TransferInterface $transfer
     * @return int
     */
    private function getResultCode(TransferInterface $transfer)
    {
        $headers = $transfer->getHeaders();

        if (isset($headers['force_result'])) {
            return (int)$headers['force_result'];
        }

        return $this->results[mt_rand(0, 1)];
    }

    /**
     * Returns response fields for result code
     *
     * @param int $resultCode
     * @return array
     */
    private function getFieldsBasedOnResponseType($resultCode)
    {
        switch ($resultCode) {
            case self::FAILURE:
                return [
                    'FRAUD_MSG_LIST' => [
                        'Stolen card',
                        'Customer location differs'
                    ]
                ];
        }

        return [];
    }
}
