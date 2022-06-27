<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as OrderTransactionCollectionFactory;
use Payoneer\OpenPaymentGateway\Model\Helper;

/**
 * Class Cancel
 * Process CANCEL request
 */
class Cancel implements HttpGetActionInterface
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var OrderTransactionCollectionFactory
     */
    protected $orderTransactionCollectionFactory;

    /**
     * Helper constructor.
     * @param Context $context
     * @param CartRepositoryInterface $cartRepository
     * @param PageFactory $resultPageFactory
     * @param Helper $helper
     * @param RedirectFactory $resultRedirectFactory
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderTransactionCollectionFactory $orderTransactionCollectionFactory
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        PageFactory $resultPageFactory,
        Helper $helper,
        RedirectFactory $resultRedirectFactory,
        TransactionRepositoryInterface $transactionRepository,
        OrderTransactionCollectionFactory $orderTransactionCollectionFactory
    ) {
        $this->context = $context;
        $this->cartRepository = $cartRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->helper = $helper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->transactionRepository = $transactionRepository;
        $this->orderTransactionCollectionFactory = $orderTransactionCollectionFactory;
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        $reqParams = $this->context->getRequest()->getParams();

        try {
            $this->helper->setPayoneerInvalidTxnSession();
            // Place order with invalid transaction details
            $this->saveCartAndPlaceOrder($reqParams);
            // Redirect to cart with previous cart data
            $this->helper->redirectToReorderCart();
            // Update the invalid order transaction type to void
            $this->updateTransactionType();
            //Add comment to the order
            $this->helper->addCommentToOrder();
        } catch (\Exception $e) {}
        return $this->helper->redirectToCart(
            __('Something went wrong while processing payment.')
        );
    }

    /**
     * @param array <mixed> $reqParams
     * @throws CouldNotSaveException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @return void
     */
    public function saveCartAndPlaceOrder($reqParams)
    {
        $cartId = $reqParams['cart_id'];
        if ($cartId) {
            /** @var Quote $quote */
            $quote = $this->cartRepository->getActive($reqParams['cart_id']);
            $payment = $quote->getPayment();
            foreach ($reqParams as $key => $value) {
                $payment->setAdditionalInformation($key, $value);
            }
            $quote->setPayment($payment);

            if (!$quote->getCustomerId()) {
                $quote = $this->helper->setGuestCustomerEmail($quote);
            }
            $this->cartRepository->save($quote);

            $this->helper->placeOrder($reqParams['cart_id']);
        }
    }

    /**
     * Updates transaction type to 'void'
     *
     * @return void
     */
    public function updateTransactionType()
    {
        $orderId = $this->helper->getLastOrderId();
        $collection = $this->orderTransactionCollectionFactory->create();
        $collection->addOrderIdFilter($orderId);
        if ($collection->getSize()) {
            /** @var  Transaction $transaction */
            $transaction = $collection->getFirstItem();
            $transaction->setTxnType('void');
            $transaction->setIsClosed(1);
            $this->transactionRepository->save($transaction);
        }
    }
}
