<?php

namespace Payoneer\OpenPaymentGateway\Model\Checkout;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\Checkout\Model\Cart\CartInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\ListUpdateTransactionService;

/**
 * Shopping cart model
 *
 * @api
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Cart extends \Magento\Checkout\Model\Cart
{
    /**
     * Api status constants
     */
    const PROCEED       = 'PROCEED';
    const REASON_OK     = 'OK';
    const LIST_EXPIRED  = 'list_expired';

    /**
     * @var ListUpdateTransactionService
     */
    private $transactionService;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Checkout\Model\ResourceModel\Cart $resourceCart
     * @param Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param ManagerInterface $messageManager
     * @param StockRegistryInterface $stockRegistry
     * @param StockStateInterface $stockState
     * @param CartRepositoryInterface $quoteRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ListUpdateTransactionService $transactionService
     * @param Config $config
     * @param array <mixed> $data
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        \Magento\Checkout\Model\ResourceModel\Cart $resourceCart,
        Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        ManagerInterface $messageManager,
        StockRegistryInterface $stockRegistry,
        StockStateInterface $stockState,
        CartRepositoryInterface $quoteRepository,
        ProductRepositoryInterface $productRepository,
        ListUpdateTransactionService $transactionService,
        Config $config,
        array $data = []
    ) {
        parent::__construct(
            $eventManager,
            $scopeConfig,
            $storeManager,
            $resourceCart,
            $checkoutSession,
            $customerSession,
            $messageManager,
            $stockRegistry,
            $stockState,
            $quoteRepository,
            $productRepository,
            $data
        );
        $this->transactionService = $transactionService;
        $this->config = $config;
    }

    /**
     * Save cart
     *
     * @return $this
     * @throws LocalizedException
     */
    public function save()
    {
        $this->_eventManager->dispatch('checkout_cart_save_before', ['cart' => $this]);

        $this->getQuote()->getBillingAddress();
        $this->getQuote()->getShippingAddress()->setCollectShippingRates(true);
        $this->getQuote()->collectTotals();

        if ($this->config->isPayoneerEnabled()) {
            if ($this->isCartUpdateAllowed()) {
                $this->_checkoutSession->setPayoneerCartUpdate(true);
                $this->saveQuote();
            } else {
                $this->_checkoutSession->setPayoneerCartUpdate(false);
            }
        } else {
            $this->saveQuote();
        }

        /**
         * Cart save usually called after changes with cart items.
         */
        $this->_eventManager->dispatch('checkout_cart_save_after', ['cart' => $this]);
        $this->reinitializeState();
        return $this;
    }

    /**
     * Save quote
     *
     * @return CartInterface
     */
    public function saveQuote()
    {
        $this->quoteRepository->save($this->getQuote());
        $this->_checkoutSession->setQuoteId($this->getQuote()->getId());
        return $this;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function isCartUpdateAllowed()
    {
        $payment = $this->getQuote()->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $listId = isset($additionalInformation[Config::LIST_ID]) ? $additionalInformation[Config::LIST_ID] : null;

        if (!$listId) {
            return true;
        } else {
            /** @var array <mixed> $result */
            $result = $this->transactionService->process($this->getQuote()->getPayment(), Config::LIST_UPDATE);

            $isListExpired = $this->isListExpired($result);
            if ($isListExpired) {
                $this->resetListId($payment);
                return true;
            }

            return $this->isValidResponse($result);
        }
    }

    /**
     * Process response of embedded integration
     * @param array <mixed> $result
     * @return bool
     */
    public function isValidResponse($result)
    {
        if ($this->config->isHostedIntegration()) {
            return $this->isValidHostedResponse($result);
        } else {
            if ($result && isset($result['response']['links'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array <mixed> $result
     * @return bool
     */
    public function isListExpired($result)
    {
        if (str_contains($result['reason'], self::LIST_EXPIRED)) {
            return true;
        }
        return false;
    }

    /**
     * @param Payment $payment
     * @throws LocalizedException
     * @return void
     */
    public function resetListId($payment)
    {
        $payment->setAdditionalInformation(Config::LIST_ID, null);
        $this->getQuote()->setPayment($payment);
    }

    /**
     * @param array <mixed> $result
     * @return bool
     */
    public function isValidHostedResponse($result)
    {
        if ($result
            && isset($result['response']['interaction'])
            && isset($result['response']['interaction']['code'])
            && $result['response']['interaction']['code'] == self::PROCEED
            && $result['response']['interaction']['reason'] == self::REASON_OK
        ) {
            return true;
        } else {
            return false;
        }
    }
}
