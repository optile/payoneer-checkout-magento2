<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\SessionFactory as CheckoutSessionFactory;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Api\OrderStatusHistoryRepositoryInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class Helper
 *
 * Module helper file
 */
class Helper
{
    const MODULE_NAME = 'Payoneer_OpenPaymentGateway';

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var PayoneerTransactionRepository
     */
    protected $payoneerTransactionRepository;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CheckoutSessionFactory
     */
    private $checkoutSessionFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /** @var OrderStatusHistoryRepositoryInterface */
    private $orderStatusRepository;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Helper constructor
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param AssetRepository $assetRepository
     * @param PayoneerTransactionRepository $payoneerTransactionRepository
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     * @param CheckoutSession $checkoutSession
     * @param CartManagementInterface $cartManagement
     * @param OrderRepositoryInterface $orderRepository
     * @param CheckoutSessionFactory $checkoutSessionFactory
     * @param CartRepositoryInterface $cartRepository
     * @param OrderStatusHistoryRepositoryInterface $orderStatusRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @return void
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        AssetRepository $assetRepository,
        PayoneerTransactionRepository $payoneerTransactionRepository,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        CheckoutSession $checkoutSession,
        CartManagementInterface $cartManagement,
        OrderRepositoryInterface $orderRepository,
        CheckoutSessionFactory $checkoutSessionFactory,
        CartRepositoryInterface $cartRepository,
        OrderStatusHistoryRepositoryInterface $orderStatusRepository,
        ProductCollectionFactory $productCollectionFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->assetRepository = $assetRepository;
        $this->payoneerTransactionRepository = $payoneerTransactionRepository;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->checkoutSession = $checkoutSession;
        $this->cartManagement = $cartManagement;
        $this->orderRepository = $orderRepository;
        $this->checkoutSessionFactory = $checkoutSessionFactory;
        $this->cartRepository = $cartRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Redirects to cart
     *
     * @param string $message
     * @return Redirect
     */
    public function redirectToCart($message)
    {
        $this->messageManager->addErrorMessage($message);
        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }

    /**
     * @return void
     * @throws LocalizedException
     */
    public function redirectToReorderCart()
    {
        $orderItemProductIds = $orderItemsByProductId = [];
        $orderId = $this->getLastOrderId();
        $storeId = $this->storeManager->getStore()->getId();
        try {
            /** @var Order $order */
            $order = $this->orderRepository->get($orderId);
            foreach ($order->getItemsCollection() as $item) {
                if ($item->getParentItem() === null) {
                    $orderItemProductIds[] = $item->getProductId();
                    $orderItemsByProductId[$item->getProductId()][$item->getId()] = $item;
                }
            }
            $products = $this->getOrderProducts($storeId, $orderItemProductIds);

            $session = $this->checkoutSessionFactory->create();
            $quote = $session->getQuote();
            foreach ($orderItemsByProductId as $productId => $orderItems) {
                if (!isset($products[$productId])) {
                    continue;
                }
                $product = $products[$productId];
                foreach ($orderItems as $orderItem) {
                    $info = $orderItem->getProductOptionByCode('info_buyRequest');
                    $info = new DataObject($info);
                    $info->setQty($orderItem->getQtyOrdered());
                    $quote->addProduct($product, $info);
                }
            }
            $this->cartRepository->save($quote);
            $session->replaceQuote($quote)->unsLastRealOrderId();
        } catch (LocalizedException $e) {
            throw new LocalizedException(
                __($e->getMessage())
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __($e->getMessage())
            );
        }
    }

    /**
     * Get the static file's path
     * @param string $fileId
     * @param array <mixed> $params
     * @return string
     */
    public function getStaticFilePath($fileId, $params)
    {
        return $this->assetRepository->getUrlWithParams($fileId, $params);
    }

    /**
     * Save customer registration id
     * @param string $registrationId
     * @param int $customerId
     * @return void
     * @throws CouldNotSaveException
     */
    public function saveRegistrationId($registrationId, $customerId)
    {
        $regId = $this->getRegistrationId($customerId);
        if (!$regId) {
            $payoneerTransactionModel = $this->payoneerTransactionRepository->create();
            $payoneerTransactionModel->setCustomerId($customerId);
            $payoneerTransactionModel->setRegistrationId($registrationId);
            $this->payoneerTransactionRepository->save($payoneerTransactionModel);
        }
    }

    /**
     * Get customer registration id
     * @param int $customerId
     * @return string | null
     */
    public function getRegistrationId($customerId)
    {
        $payoneerTransaction = $this->payoneerTransactionRepository->getByCustomerId($customerId);
        return $payoneerTransaction ? $payoneerTransaction->getRegistrationId() : null;
    }

    /**
     * Format amount to 2 decimal points
     * @param float|null $amount
     * @return string|null
     */
    public function formatNumber($amount)
    {
        return $amount ? number_format($amount, 2, '.', '') : null;
    }

    /**
     * Get Magento application product metadata
     *
     * @return array <mixed>
     */
    public function getProductMetaData()
    {
        return [
            'magentoVersion'    =>  $this->productMetadata->getVersion(),
            'magentoEdition'    =>  $this->productMetadata->getEdition(),
            'moduleVersion'     =>  $this->getModuleVersion()
        ];
    }

    /**
     * Find the installed module version
     *
     * @return mixed
     */
    public function getModuleVersion()
    {
        $module = $this->moduleList->getOne(self::MODULE_NAME);
        return $module ? $module ['setup_version'] : null;
    }

    /**
     * Unset custom checkout session variable
     * @return void
     */
    public function unsetPayoneerCustomerEmailSession()
    {
        if ($this->checkoutSession->getPayoneerCustomerEmail()) {
            $this->checkoutSession->unsPayoneerCustomerEmail();
        }
    }

    /**
     * Unset custom checkout session variable
     * @return void
     */
    public function unsetPayoneerCountryIdSession()
    {
        if ($this->checkoutSession->getShippingCountryId()) {
            $this->checkoutSession->unsShippingCountryId();
        }
        if ($this->checkoutSession->getBillingCountryId()) {
            $this->checkoutSession->unsBillingCountryId();
        }
    }

    /**
     * Unset custom checkout session variable
     * @return void
     */
    public function unsetPayoneerInvalidTxnSession()
    {
        if ($this->checkoutSession->getPayoneerInvalidTxn()) {
            $this->checkoutSession->unsPayoneerInvalidTxn();
        }
    }

    /**
     * Set invalid transaction session
     *
     * @return void
     */
    public function setPayoneerInvalidTxnSession()
    {
        $this->checkoutSession->setPayoneerInvalidTxn(true);
    }

    /**
     * @param Quote $quote
     * @return Quote
     */
    public function setGuestCustomerEmail($quote)
    {
        $quote->setCheckoutMethod(CartManagementInterface::METHOD_GUEST);
        $customerEmail =  $quote->getCustomerEmail() ?: $quote->getBillingAddress()->getEmail();
        if (!$customerEmail) {
            $quote->setCustomerEmail($this->checkoutSession->getPayoneerCustomerEmail());
        }

        return $quote;
    }

    /**
     * @param int $cartId
     * @throws CouldNotSaveException
     * @return void
     */
    public function placeOrder($cartId)
    {
        $this->cartManagement->placeOrder($cartId);
    }

    /**
     * @return mixed|null
     */
    public function getLastOrderId()
    {
        return $this->checkoutSession->getData('last_order_id');
    }

    /**
     * Add comment to the order history
     *
     * @return void
     * @throws CouldNotSaveException
     */
    public function addCommentToOrder()
    {
        $order = $this->orderRepository->get($this->getLastOrderId());
        /** @phpstan-ignore-next-line */
        $comment = $order->addStatusHistoryComment('Transaction voided.');
        $this->orderStatusRepository->save($comment);
    }

    /**
     * Get the product collection from the order item product ids.
     *
     * @param int $storeId
     * @param array <mixed> $orderItemProductIds
     * @return array <mixed>
     */
    public function getOrderProducts($storeId, array $orderItemProductIds): array
    {
        /** @var ProductCollection $collection */
        $collection = $this->productCollectionFactory->create();
        $collection->setStore($storeId)
            ->addIdFilter($orderItemProductIds)
            ->addStoreFilter()
            ->addAttributeToSelect('*')
            ->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner')
            ->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner')
            ->addOptionsToResult();

        return $collection->getItems();
    }
}
