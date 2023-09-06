<?php
namespace Payoneer\OpenPaymentGateway\Model\Config\Backend;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config as PayoneerConfig;
use Payoneer\OpenPaymentGateway\Model\Api\Request;

/**
 * Validate the API credentials before save
 */
class ApiValidate extends \Magento\Framework\App\Config\Value
{
    const XML_PATH_ACTIVE = 'groups/payoneer/fields/active/value';
    const XML_PATH_ENVIRONMENT = 'groups/payoneer/fields/environment/value';
    const XML_PATH_MERCHANT_GATEWAY = 'groups/payoneer/fields/merchant_gateway_key/value';
    const XML_PATH_SANDBOX_API_KEY = 'groups/payoneer/fields/sandbox_api_key/value';
    const XML_PATH_SANDBOX_HOST_NAME = 'payment/payoneer/sandbox_host_name';
    const XML_PATH_SANDBOX_STORE_CODE = 'groups/payoneer/fields/sandbox_store_code/value';
    const XML_PATH_LIVE_API_KEY = 'groups/payoneer/fields/live_api_key/value';
    const XML_PATH_LIVE_HOST_NAME = 'payment/payoneer/live_host_name';
    const XML_PATH_LIVE_STORE_CODE = 'groups/payoneer/fields/live_store_code/value';

    /**
     * @var PayoneerConfig
     */
    protected $payoneerConfig;

    /**
     * @var Request
     */
    protected $apiRequest;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param PayoneerConfig $payoneerConfig
     * @param Request $apiRequest
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        PayoneerConfig $payoneerConfig,
        Request $apiRequest,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->payoneerConfig = $payoneerConfig;
        $this->apiRequest = $apiRequest;
    }

    /**
     * Validates Api call
     * @return $this
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        if (!$this->getData(self::XML_PATH_ACTIVE)) {
            return $this;
        }

        if ($this->getData(self::XML_PATH_ENVIRONMENT) == 'test') {
            $apiKey = $this->getData(self::XML_PATH_SANDBOX_API_KEY);
            $hostName = $this->getHostValue(self::XML_PATH_SANDBOX_HOST_NAME);
            $storeCode = $this->getData(self::XML_PATH_SANDBOX_STORE_CODE);
        } else {
            $apiKey = $this->getData(self::XML_PATH_LIVE_API_KEY);
            $hostName = $this->getHostValue(self::XML_PATH_LIVE_HOST_NAME);
            $storeCode = $this->getData(self::XML_PATH_LIVE_STORE_CODE);
        }

        $credentials['merchantCode'] = $this->getData(self::XML_PATH_MERCHANT_GATEWAY);
        $credentials['apiKey'] = $apiKey;
        $credentials['hostName'] = $hostName;

        $data = $this->payoneerConfig->getMockData();
        if ($storeCode) {
            $data['division'] = $storeCode;
        }

        $response = $this->apiRequest->send(
            PayoneerConfig::METHOD_POST,
            PayoneerConfig::LIST_END_POINT,
            $credentials,
            $data
        );

        if ($response->getData('status') !== 200) {
            throw new LocalizedException(__(
                'Payoneer validation failed. Make sure the credentials you have entered are correct.'
            ));
        }

        return $this;
    }

    /**
     * get api host value
     * @param string $path
     * @return string
     */
    private function getHostValue($path): string
    {
        return (string)$this->_config->getValue(
            $path,
            $this->getScope() ?: ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            $this->getScopeCode()
        );
    }
}
