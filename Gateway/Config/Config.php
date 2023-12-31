<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Source\Fields as AdminFields;
use Payoneer\OpenPaymentGateway\Model\Ui\ConfigProvider;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Locale\Resolver as LocaleResolver;

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
    const METHOD_PUT    = 'PUT';
    const METHOD_POST   = 'POST';
    const METHOD_DELETE = 'DELETE';

    /**
     * API endpoints
     */
    const LIST_END_POINT                    =   'api/lists';
    const LIST_UPDATE_END_POINT             =   'api/lists/%s'; //api/lists/{longId}
    const CAPTURE_END_POINT                 =   'api/charges/%s/closing'; //api/charges/{longId}/closing
    const REFUND_END_POINT                  =   'api/charges/%s/payout'; //api/charges/{longId}/payout
    const AUTHORIZATION_CANCEL_END_POINT    =   'api/charges/%s'; //api/charges/{longId}

    /**
     * List operation constants
     */
    const LIST_CAPTURE  = 'list_capture';
    const LIST_FETCH    = 'list_fetch';
    const LIST_UPDATE   = 'list_update';
    const LIST_DELETE   = 'list_delete';

    /**
     * Country path
     */
    const COUNTRY_CODE_PATH = 'general/country/default';

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

    /**
     * Notification configuration constants
     */
    const NOTIFICATION_CLEANUP_DAYS_PATH    =   'notification_settings/cleanup_days';
    const EMAIL_NOTIFICATION_DAYS_PATH      =   'notification_settings/send_email_days';

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
    const LIST_ID               =   'listId';
    const LANGUAGE              =   'language';
    const REDIRECT_URL          =   'redirect_url';

    const ENTITY_PAYMENT        =   'payment';

    const HOST_NAME             =   'host_name';
    const HOSTED                =   'HOSTED';
    const SELECT_NATIVE         =   'SELECTIVE_NATIVE';
    const INTEGRATION_EMBEDDED  =   'embedded';
    const INTEGRATION_HOSTED    =   'hosted';
    const PAYMENT_FLOW          =   'payment_flow';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LocaleResolver
     */
    protected $localeResolver;

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
     * @param LocaleResolver $localeResolver
     * @param string $methodCode
     * @param string $pathPattern
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig,
        LocaleResolver $localeResolver,
        $methodCode = ConfigProvider::CODE,
        $pathPattern = self::DEFAULT_PATH_PATTERN
    ) {
        parent::__construct($scopeConfig, $methodCode, $pathPattern);
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->methodCode = $methodCode;
        $this->pathPattern = $pathPattern;
        $this->localeResolver = $localeResolver;
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
            'transactionId' => 't-'.time(),
            'country' => 'US',
            'integration' => 'SELECTIVE_NATIVE',
            'customer' => [
                'number' => 'c-'.time(),
                'email' => 'e-'.time().'@example.com',
                'addresses' => [
                    'shipping' => [
                        'street' => 'Ganghoferstr.',
                        'houseNumber' => '39a',
                        'zip' => '81241',
                        'city' => 'New York',
                        'country' => 'US',
                        'id' => 'shipping',
                        'name' => [
                            'firstName' => 'Terry',
                            'lastName' => 'Jerry'
                        ]
                    ],
                    'billing' => [
                        'street' => 'Ganghoferstr.',
                        'houseNumber' => '39b',
                        'zip' => '80339',
                        'city' => 'New York',
                        'country' => 'US',
                        'id' => 'billing',
                        'name' => [
                            'firstName' => 'Terry',
                            'lastName' => 'Jerry'
                        ]
                    ]
                ],
                'phones' => [
                    'mobile' => [
                        'unstructuredNumber' => '49089880088'
                    ]
                ]
            ],
            'payment' => [
                'reference' => 'pr-'.time(),
                'amount' => 9.99,
                'netAmount' => 9.99,
                'currency' => 'USD',
                'invoiceId' => 'i-'.time()
            ],
            'clientInfo' => [
                'timezone' => 'Europe/Berlin',
                'javaEnabled' => true,
                'language' => 'de-DE',
                'colorDepth' => 24,
                'browserScreenWidth' => 1521,
                'browserScreenHeight' => 391,
                'headers' => [
                    [
                        'name' => 'User-Agent',
                        'value' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36'
                    ]
                ]
            ],
            'style' => [
                'hostedVersion' => 'v4'
            ],
            'products' => [
                [
                    'code' => 'PN-001276',
                    'name' => 'Juggling Balls (R)',
                    'amount' => 9.99,
                    'netAmount' => 9.99,
                    'taxAmount' => 0,
                    'taxRatePercentage' => 10,
                    'currency' => 'USD',
                    'quantity' => 1,
                    'shippingAddressId' => 'billing',
                    'type' => 'DIGITAL'
                ]
            ],
            'callback' => [
                'returnUrl' => 'https://dev.oscato.com/shop/success.html',
                'cancelUrl' => 'https://dev.oscato.com/shop/cancel.html',
                'notificationUrl' => 'https://dev.oscato.com/morpritam'
            ],
            'preselection' => [
                'deferral' => 'ANY'
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
            case self::HOST_NAME:
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
                $styleConfigValues[$key] = trim($value);
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

    /**
     * Get Country code by website scope
     *
     * @return string
     */
    public function getCountryByStore(): string
    {
        return $this->scopeConfig->getValue(
            self::COUNTRY_CODE_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Return the current store locale
     *
     * @return string
     */
    public function getStoreLocale(): string
    {
        $storeLocale = $this->localeResolver->getLocale();
        $languageMapping = $this->getValue('language_mapping');
        if ($languageMapping != '') {
            $languageMappings = explode(',', $languageMapping);
            foreach ($languageMappings as $language) {
                $localeParts = explode(':', $language);
                if (count($localeParts) > 1 && $localeParts[0] == $storeLocale) {
                    return $localeParts[1];
                }
            }
        }
        return $storeLocale;
    }
}
