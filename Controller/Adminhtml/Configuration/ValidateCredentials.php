<?php

namespace Payoneer\OpenPaymentGateway\Controller\Adminhtml\Configuration;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Api\Request;

/**
 * Class ValidateCredentials
 * Validate the API Credentials
 */
class ValidateCredentials extends Action
{
    const ADMIN_RESOURCE = 'Magento_Config::config';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Request
     */
    private $request;

    /**
     * Json Factory
     *
     * @var JsonFactory
     */
    private $jsonResultFactory;

    /**
     * ValidateCredentials constructor.
     * @param Action\Context $context
     * @param Config $config
     * @param Request $request
     * @param JsonFactory $jsonResultFactory
     */
    public function __construct(
        Action\Context $context,
        Config $config,
        Request $request,
        JsonFactory $jsonResultFactory
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->request = $request;
        $this->jsonResultFactory = $jsonResultFactory;
    }

    /**
     * Validates the field values
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $gatewayResponse = [];
        $endPoint = Config::LIST_END_POINT;
        $data = $this->config->getMockData();
        $storeCode = $this->getRequest()->getParam('storeCode');

        $credentials['merchantCode'] = $this->getRequest()->getParam('merchantCode');
        $credentials['apiKey'] = $this->getRequest()->getParam('apiKey');
        $hostName = $this->getRequest()->getParam('hostName');
        if ($hostName) {
            $credentials['hostName'] = $hostName;
        } else {
            $credentials['hostName'] = $this->config->getCredentials($this->config::HOST_NAME);
        }
        if ($storeCode) {
            $data['division'] = $storeCode;
        }

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $gatewayResponse = $this->request->send(
                Config::METHOD_POST,
                $endPoint,
                $credentials,
                $data
            );
            $response->setHttpResponseCode($gatewayResponse['status']);
        } catch (Exception $e) {
            $response->setHttpResponseCode(400);
        }
        $result = $this->jsonResultFactory->create();
        $responseData = isset($gatewayResponse['response'])
            ? $gatewayResponse['response'] : [];
        return $result->setData(['data' => $responseData]);
    }
}
