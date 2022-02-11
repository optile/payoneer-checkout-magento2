<?php

namespace Payoneer\OpenPaymentGateway\Controller\Adminhtml\Configuration;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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
     * ValidateCredentials constructor.
     * @param Action\Context $context
     * @param Config $config
     * @param Request $request
     */
    public function __construct(
        Action\Context $context,
        Config $config,
        Request $request
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * @return ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute(): ResultInterface
    {
        $endPoint = Config::END_POINT;
        $data = $this->config->getMockData();
        $apiKey = $this->getRequest()->getParam('apiKey');
        $merchantCode = $this->getRequest()->getParam('merchantCode');
        $storeCode = $this->getRequest()->getParam('storeCode');
        $hostName = $this->getRequest()->getParam('hostName');
        $storeId = $this->getRequest()->getParam('storeId', 0);
        $environment = $this->getRequest()->getParam('environment');

        $credentials['merchantCode'] = $merchantCode;
        $credentials['apiKey'] = $apiKey;
        $credentials['hostName'] = $hostName;

        if ($storeCode) {
            $data["division"] = $storeCode;
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

        return $response;
    }

    /**
     * @param $environment
     * @param $storeId
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setFieldValues($environment, $storeId)
    {
        if ($environment === AdminFields::ENVIRONMENT_SANDBOX) {
            $this->fields['apiKey'] = $this->config->getConfig('sandbox_api_key', $storeId);
            $this->fields['storeCode'] = $this->config->getConfig('sandbox_store_code', $storeId);
            $this->fields['hostName'] = $this->config->getConfig('sandbox_host_name', $storeId);
        } else {
            $this->fields['apiKey'] = $this->config->getConfig('live_api_key', $storeId);
            $this->fields['storeCode'] = $this->config->getConfig('live_store_code', $storeId);
            $this->fields['hostName'] = $this->config->getConfig('live_host_name', $storeId);
        }
    }
}
