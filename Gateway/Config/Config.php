<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Source\Fields as AdminFields;
use Payoneer\OpenPaymentGateway\Model\Ui\ConfigProvider;

/**
 * Class Config
 *
 * Payoneer configurations
 */
class Config extends \Magento\Payment\Gateway\Config\Config
{
    const DEFAULT_PATH_PATTERN = 'payment/%s/%s';

    /**
     * API method types
     */
    const METHOD_GET    = 'GET';
    const METHOD_POST   = 'POST';
    const METHOD_DELETE = 'DELETE';

    /**
     * API endpoints
     */
    const END_POINT                         =   'api/lists';
    const CAPTURE_END_POINT                 =   'api/charges/%s/closing'; //api/charges/{longId}/closing
    const REFUND_END_POINT                  =   'api/charges/%s/payout'; //api/charges/{longId}/payout
    const AUTHORIZATION_CANCEL_END_POINT    =   'api/charges/%s'; //api/charges/{longId}

    /**
     * List operation constants
     */
    const LIST_CAPTURE  = 'list_capture';
    const LIST_FETCH    = 'list_fetch';

    /**
     * API Request constants
     */
    const CALLBACK              =   'callback';
    const RETURN_URL            =   'returnUrl';
    const CANCEL_URL            =   'cancelUrl';
    const NOTIFICATION_URL      =   'notificationUrl';
    const RETURN_URL_PATH       =   'payoneer/redirect/success';
    const CANCEL_URL_PATH       =   'payoneer/redirect/cancel';
    const NOTIFICATION_URL_PATH =   'payoneer/redirect/notification';
    const EMBEDDED_STYLE_PATH   =   'payoneer/embedded/style';

    const PRESELECTION          =   'preselection';
    const DEFERRAL              =   'deferral';

    const PAYMENT               =   'payment';
    const AMOUNT                =   'amount';
    const CURRENCY              =   'currency';
    const REFERENCE             =   'reference';
    const CUSTOMER              =   'customer';
    const NUMBER                =   'number';
    const EMAIL                 =   'email';
    const COMPANY               =   'company';
    const NAME                  =   'name';
    const TITLE                 =   'title';
    const FIRST_NAME            =   'firstName';
    const MIDDLE_NAME           =   'middleName';
    const LAST_NAME             =   'lastName';
    const SHIPPING              =   'shipping';
    const BILLING               =   'billing';
    const ADDRESSES             =   'addresses';
    const STREET                =   'street';
    const HOUSE_NUMBER          =   'houseNumber';
    const ZIP                   =   'zip';
    const POSTCODE              =   'postcode';
    const CITY                  =   'city';
    const STATE                 =   'state';
    const REGION                =   'region';
    const COUNTRY               =   'country';
    const COUNTRY_ID            =   'countryId';
    const TRANSACTION_ID        =   'transactionId';
    const INTEGRATION           =   'integration';
    const DIVISION              =   'division';
    const ALLOW_DELETE          =   'allowDelete';
    const PRODUCTS              =   'products';
    const SKU                   =   'code';
    const QUANTITY              =   'quantity';
    const TYPE                  =   'type';
    const NET_AMOUNT            =   'netAmount';
    const TAX_AMOUNT            =   'taxAmount';
    const TAX_PERCENT           =   'taxRatePercentage';
    const INVOICE_ID            =   'invoiceId';
    const STYLE                 =   'style';
    const RESOLUTION_1X         =   '1x';
    const HOSTED_VERSION        =   'hostedVersion';
    const VERSION_V4            =   'v4';
    const TOKEN_ID              =   'token';
    const TXN_ID                =   'transaction_id';
    const REGISTRATION          =   'registration';
    const ID                    =   'id';
    const TOKEN                 =   'token';
    const TOKEN_NOTIFICATION    =   'notification_token';

    const ENTITY_PAYMENT        =   'payment';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string|null
     */
    private $methodCode;/** @phpstan-ignore-line */

    /**
     * @var string|null
     */
    private $pathPattern;/** @phpstan-ignore-line */

    /**
     * @var string[]
     */
    protected $styleConfigs = [
        'background-color' => 'payment/payoneer/widget_appearance/background_color',
        'color' => 'payment/payoneer/widget_appearance/color',
        'font-size' => 'payment/payoneer/widget_appearance/font_size',
        'font-weight' => 'payment/payoneer/widget_appearance/font_weight',
        'letter-spacing' => 'payment/payoneer/widget_appearance/letter_spacing',
        'line-height' => 'payment/payoneer/widget_appearance/line_height',
        'padding' => 'payment/payoneer/widget_appearance/padding',
        'text-align' => 'payment/payoneer/widget_appearance/text_align',
    ];

    /**
     * Config constructor.
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        $methodCode = ConfigProvider::CODE,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->methodCode = $methodCode;
        $this->pathPattern = $pathPattern;
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
        return $this->storeManager->getStore()->getCurrentCurrencyCode();/** @phpstan-ignore-line */
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

    /**
     * Prepare header for API requests
     *
     * @param null $merchantCode
     * @param null $appKey
     * @return array <mixed>
     */
    public function prepareHeaders(
        $merchantCode = null,
        $appKey = null
    ): array {
        $headers = [];
        $headers['Content-Type'] = 'application/vnd.optile.payment.enterprise-v1-extensible+json';
        $headers['Accept'] = 'application/vnd.optile.payment.enterprise-v1-extensible+json';
        $headers['Authorization'] = 'Basic ' . base64_encode($merchantCode . ':' . $appKey);
        return $headers;
    }

    /**
     * Get environment credentials
     *
     * @param string $key
     * @return mixed|string|null
     */
    public function getCredentials($key)
    {
        $environment = $this->getValue('environment');
        switch ($key) {
            case 'api_key':
                return ($environment && $environment == AdminFields::ENVIRONMENT_SANDBOX_VALUE) ?
                    $this->getValue('sandbox_api_key') :
                    $this->getValue('live_api_key');
            case 'store_code':
                return ($environment && $environment == AdminFields::ENVIRONMENT_SANDBOX_VALUE) ?
                    $this->getValue('sandbox_store_code') :
                    $this->getValue('live_store_code');
            case 'host_name':
                return ($environment && $environment == AdminFields::ENVIRONMENT_SANDBOX_VALUE) ?
                    $this->getValue('sandbox_host_name') :
                    $this->getValue('live_host_name');
        }
        return null;
    }

    /**
     * Check if hosted integration
     * @return bool
     */
    public function isHostedIntegration()
    {
        return $this->getValue('payment_flow') == AdminFields::HOSTED;
    }

    /**
     * Check if Payoneer module is enabled
     * @return mixed|null
     */
    public function isPayoneerEnabled()
    {
        return $this->getValue('active');
    }

    /**
     * Get style configuration values
     * @return array <mixed>
     */
    public function getStyleConfig()
    {
        $styleConfigValues = [];
        foreach ($this->styleConfigs as $key => $path) {
            $value = $this->scopeConfig->getValue($path);
            if ($value) {
                $styleConfigValues[$key] = $value;
            }
        }
        return $styleConfigValues;
    }

    /**
     * Check if module debugging is enabled or not
     *
     * @return bool
     */
    public function isDebuggingEnabled()
    {
        return $this->getValue('debug');
    }
}
