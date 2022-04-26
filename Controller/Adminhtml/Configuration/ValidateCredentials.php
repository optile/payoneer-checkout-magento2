<?php

namespace Payoneer\OpenPaymentGateway\Controller\Adminhtml\Configuration;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Source\Fields as AdminFields;
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
    protected $config;

    /**
     * @var array <mixed>
     */
    protected $fields = [];

    /**
     * @var Request
     */
    protected $request;

    /**
     * Json Factory
     *
     * @var JsonFactory
     */
    protected $jsonResultFactory;

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
        $endPoint = Config::END_POINT;
        $data = $this->config->getMockData();
        $storeCode = $this->getRequest()->getParam('storeCode');
        $storeId = $this->getRequest()->getParam('storeId', 0);
        $environment = $this->getRequest()->getParam('environment');

        $credentials['merchantCode'] = $this->getRequest()->getParam('merchantCode');
        $credentials['apiKey'] = $this->getRequest()->getParam('apiKey');
        $credentials['hostName'] = $this->getRequest()->getParam('hostName');

        if ($storeCode) {
            $data['division'] = $storeCode;
        }

        $this->setFieldValues($environment, $storeId);
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

    /**
     * Set the field values
     * @param string $environment
     * @param mixed $storeId
     * @return void
     */
    public function setFieldValues($environment, $storeId)
    {
        if ($environment === AdminFields::ENVIRONMENT_SANDBOX_VALUE) {
            $this->fields['apiKey'] = $this->config->getValue('sandbox_api_key', $storeId);
            $this->fields['storeCode'] = $this->config->getValue('sandbox_store_code', $storeId);
            $this->fields['hostName'] = $this->config->getValue('sandbox_host_name', $storeId);
        } else {
            $this->fields['apiKey'] = $this->config->getValue('live_api_key', $storeId);
            $this->fields['storeCode'] = $this->config->getValue('live_store_code', $storeId);
            $this->fields['hostName'] = $this->config->getValue('live_host_name', $storeId);
        }
    }
}
