<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Http\Client;

use Magento\Framework\DataObject;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
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
     * @param Logger $logger
     * @param Request $request
     */
    public function __construct(
        Logger $logger,
        Request $request
    ) {
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|DataObject
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $response = [];
        $credentials['merchantCode'] = 'MRS_TEST_TRYZENS';
        $credentials['apiKey'] = 'v3e7es43uj3qnfocl2thi5ccle245ta7g38s03t1';
        $credentials['hostName'] = 'https://api.sandbox.oscato.com/';
        $data = $transferObject->getBody();
        file_put_contents(BP.'/var/log/payoneer.log','REQUEST:: '.
            json_encode($data).PHP_EOL, FILE_APPEND);
        $responseObj = $this->request->send(
            $transferObject->getMethod(),
            'api/lists',
            $credentials,
            $data
        );

        $response['response'] = $responseObj->getData('response');
        $response['status'] = $responseObj->getData('status');
        $response['reason'] = $responseObj->getData('reason');
        /* $this->logger->debug(
             [
                 'request' => $transferObject->getBody(),
                 'response' => $response
             ]
         );*/

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
