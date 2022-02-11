<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Config;

use Magento\Config\Model\ResourceModel\Config as ConfigResource;
use Magento\Eav\Model\Entity;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class Config extends \Magento\Payment\Gateway\Config\Config
{
    /**
     * Module Name
     */
    const MODULE_NAME = 'Payoneer_OpenPaymentGateway';

    /**
     * API method types
     */
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';

    /**
     * API endpoints
     */
    const END_POINT = 'api/lists';

    /**
     * API Request constants
     */

    const CALLBACK = 'callback';
    const RETURN_URL = 'returnUrl';
    const CANCEL_URL = 'cancelUrl';
    const NOTIFICATION_URL = 'notificationUrl';
    const PAYMENT = 'payment';
    const AMOUNT = 'amount';
    const CURRENCY = 'currency';
    const REFERENCE = 'reference';
    const CUSTOMER = 'customer';
    const NUMBER = 'number';
    const EMAIL = 'email';
    const COMPANY = 'company';
    const NAME = 'name';
    const TITLE = 'title';
    const FIRST_NAME = 'firstName';
    const MIDDLE_NAME = 'middleName';
    const LAST_NAME = 'lastName';
    const SHIPPING = 'shipping';
    const BILLING = 'billing';
    const ADDRESSES = 'addresses';
    const USE_BILLING_AS_SHIPPING = 'useBillingAsShippingAddress';

    /**
     * @var array<int, string>
     */
    protected $storeCode = [];

    /**
     * @var mixed[]
     */

    protected $config = [
        'payoneer_active' => ['path' => 'payment/payoneer/active'],
        'environment' => ['path' => 'payment/payoneer/environment'],
        'merchant_gateway_key' => ['path' => 'payment/payoneer/merchant_gateway_key'],
        'live_api_key' => ['path' => 'payment/payoneer/live_api_key'],
        'live_store_code' => ['path' => 'payment/payoneer/live_store_code'],
        'live_host_name' => ['path' => 'payment/payoneer/live_host_name'],
        'sandbox_api_key' => ['path' => 'payment/payoneer/sandbox_api_key'],
        'sandbox_store_code' => ['path' => 'payment/payoneer/sandbox_store_code'],
        'sandbox_host_name' => ['path' => 'payment/payoneer/sandbox_host_name'],
        'payment_action' => ['path' => 'payment/payoneer/payment_action'],
        'payment_flow' => ['path' => 'payment/payoneer/payment_flow'],
        'order_reference_message' => ['path' => 'payment/payoneer/order_reference_message'],
        'sort_order' => ['path' => 'payment/payoneer/sort_order']
    ];

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var ConfigResource
     */
    protected $configResource;

    /**
     * @var Entity
     */
    protected $entity;

    /**
     * Config constructor.
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     * @param ConfigResource $configResource
     * @param null $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor,
        ConfigResource $configResource,
        $methodCode = null,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
        $this->configResource = $configResource;
    }

    /**
     * @param string $key
     * @param int|null $scopeId
     * @param string $scope
     * @return mixed|string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getConfig($key, $scopeId = null, $scope = ScopeInterface::SCOPE_STORE)
    {
        $config='';
        if (isset($this->config[$key]['path'])) {
            $configPath = $this->config[$key]['path'];
            if ($scopeId === null) {
                $scopeId = $this->storeManager->getStore()->getId();
            }
            if (isset($this->config[$key]['read_from_db'])) {
                $config = $this->getConfigFromDb($configPath, $scope, $scopeId);
            } else {
                $config = $this->scopeConfig->getValue($configPath, $scope, $scopeId);
            }

            if (isset($this->config[$key]['encrypted']) && $this->config[$key]['encrypted'] === true && $config) {
                $config = $this->encryptor->decrypt($config);
            }
        }
        return $config;
    }

    /**
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     * @return string
     * @throws LocalizedException
     */
    public function getConfigFromDb($path, $scope = ScopeInterface::SCOPE_STORES, $scopeId = 0)
    {
        if ($scope == ScopeInterface::SCOPE_STORE) {
            $scope = ScopeInterface::SCOPE_STORES;
        }
        $connection = $this->configResource->getConnection();
        if (!$connection) {
            return '';
        }
        $select = $connection->select()->from(
            $this->configResource->getMainTable(),
            ['value']
        )->where(
            'path = ?',
            $path
        )->where(
            'scope = ?',
            $scope
        )->where(
            'scope_id = ?',
            $scopeId
        );
        return $connection->fetchOne($select);
    }

    /**
     * Check if Payoneer module is enabled
     *
     * @param int|null $scopeId
     * @param string $scope
     * @return boolean
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isModuleEnabled($scopeId = null, $scope = ScopeInterface::SCOPE_STORE): bool
    {
        return (bool)$this->getConfig('payoneer_active', $scopeId, $scope);
    }

    /**
     * Get store identifier
     *
     * @return  int
     * @throws NoSuchEntityException
     */
    public function getStoreId(): int
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Get website identifier
     *
     * @return  int
     * @throws NoSuchEntityException
     */
    public function getWebsiteId(): int
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * @param string $type
     * @return string
     * @throws NoSuchEntityException
     */
    public function getBaseUrl($type = UrlInterface::URL_TYPE_WEB): string
    {
        return $this->storeManager->getStore()->getBaseUrl($type);
    }

    /**
     * @return string
     * @throws NoSuchEntityException
     */
    public function getCurrentCurrency(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Get default website ID
     *
     * @return int
     */
    public function getDefaultWebsiteId(): int
    {
        $websiteId = 0;
        $storeView = $this->storeManager->getDefaultStoreView();
        if ($storeView) {
            $websiteId = $storeView->getWebsiteId();
        }
        return $websiteId;
    }

    /**
     * Get default store ID from website
     *
     * @param int $websiteId
     * @return int
     * @throws LocalizedException
     */
    public function getDefaultStoreId($websiteId): int
    {
        $storeId = 0;
        /** @var Website $website */
        $website = $this->storeManager->getWebsite($websiteId);
        if ($website instanceof Website) {
            $storeId = $website->getDefaultStore()->getId();
        }
        return $storeId;
    }

    /**
     * Check if system is run in the single store mode
     *
     * @return bool
     */
    public function isSingleStoreMode()
    {
        return $this->storeManager->isSingleStoreMode();
    }

    /**
     * Get Store Code by Store ID
     * @param int $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getStoreName($storeId): string
    {
        if (!array_key_exists($storeId, $this->storeCode)) {
            $this->storeCode[$storeId] = $this->storeManager->getStore($storeId)->getName();
        }
        return $this->storeCode[$storeId];
    }

    /**
     * @return array <mixed>
     */
    public function getMockData()
    {
        return [
            'transactionId' => '21-0005',
            'country' => 'DE',
            'customer' => [
                'email' => 'test@test.com'
            ],
            'payment' => [
                'amount' => 0.89,
                'currency' => 'EUR',
                'reference' => 'Shop 101/20-03-2016'
            ],
            'callback' => [
                'returnUrl' => 'https://resources.integration.oscato.com/paymentpage/v3-examples/success.html',
                'cancelUrl' => 'https://resources.integration.oscato.com/paymentpage/v3-examples/cancel.html'
            ]
        ];
    }
}
