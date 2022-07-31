<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Page;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as OrderTransactionCollectionFactory;
use Payoneer\OpenPaymentGateway\Model\Helper;
use Payoneer\OpenPaymentGateway\Logger\NotificationLogger;

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
     * @var Helper
     */
    private $helper;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * @var OrderTransactionCollectionFactory
     */
    protected $orderTransactionCollectionFactory;

    /**
     * @var NotificationLogger
     */
    protected $notificationLogger;

    /**
     * Helper constructor.
     * @param Context $context
     * @param CartRepositoryInterface $cartRepository
     * @param Helper $helper
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderTransactionCollectionFactory $orderTransactionCollectionFactory
     * @param NotificationLogger $notificationLogger
     */
    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        Helper $helper,
        TransactionRepositoryInterface $transactionRepository,
        OrderTransactionCollectionFactory $orderTransactionCollectionFactory,
        NotificationLogger $notificationLogger
    ) {
        $this->context = $context;
        $this->cartRepository = $cartRepository;
        $this->helper = $helper;
        $this->transactionRepository = $transactionRepository;
        $this->orderTransactionCollectionFactory = $orderTransactionCollectionFactory;
        $this->notificationLogger = $notificationLogger;
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
        } catch (\Exception $e) {
            $this->notificationLogger->addError(
                'CancelError - ' . $e->getMessage()
            );
        }
        return $this->helper->redirectToCart(
            __('We couldn\'t process the payment')
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
