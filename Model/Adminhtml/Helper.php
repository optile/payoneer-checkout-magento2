<?php
namespace Payoneer\OpenPaymentGateway\Model\Adminhtml;

use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionSearchResultInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Ui\ConfigProvider;

/**
 * Class Helper
 *
 * Module helper file for backend
 */
class Helper
{
    const AUTHORIZATION = 'authorization';
    const CAPTURE = 'capture';
    const CHARGED = 'charged';
    const DEBITED = 'debited';

    /**
     * @var TransactionSearchResultInterfaceFactory
     */
    protected $transactions;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var InvoiceService
     */
    protected $invoiceService;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var InvoiceSender
     */
    protected $invoiceSender;

    /**
     * Helper constructor.
     * @param TransactionSearchResultInterfaceFactory $transactions
     * @param OrderRepositoryInterface $orderRepository
     * @param ManagerInterface $messageManager
     * @param InvoiceService $invoiceService
     * @param InvoiceSender $invoiceSender
     * @param Transaction $transaction
     * @param Config $config
     */
    public function __construct(
        TransactionSearchResultInterfaceFactory $transactions,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $messageManager,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        Transaction $transaction,
        Config $config
    ) {
        $this->transactions = $transactions;
        $this->orderRepository = $orderRepository;
        $this->messageManager = $messageManager;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transaction = $transaction;
        $this->config = $config;
    }

    /**
     * Get the transaction type
     * @param OrderPaymentInterface $payment
     * @return string
     */
    public function getTransactionType($payment)
    {
        $transactionType = null;
        $transactions =
            $this->transactions/** @phpstan-ignore-line */
            ->create()
                ->addPaymentIdFilter($payment->getEntityId());
        $transactionItems = $transactions->getItems();
        foreach ($transactionItems as $transaction) {
            $transactionType = $transaction->getData('txn_type');
        }

        return $transactionType;
    }

    /**
     * Check if Payoneer capture button can be shown
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function canShowCaptureBtn(OrderInterface $order)
    {
        if (!$this->isPayoneerEnabled()) {
            return false;
        } else {
            $payment = $order->getPayment();
            if ($payment) {
                if ($payment->getMethod() !== ConfigProvider::CODE) {
                    return false;
                }
                $transactionType = $this->getTransactionType($payment);
                switch ($transactionType) {
                    case self::AUTHORIZATION:
                        return true;
                    case self::CAPTURE:
                        $additionalInformation = $payment->getAdditionalInformation();
                        if (!isset($additionalInformation['payoneerCapture'])) {
                            return true;
                        } else {
                            return false;
                        }
                }
            }
        }
        return false;
    }

    /**
     * @param int $orderId
     * @return OrderInterface|null
     */
    public function getOrder($orderId)
    {
        if (!$this->order instanceof OrderInterface) {
            try {
                $this->order = $this->orderRepository->get($orderId);
            } catch (NoSuchEntityException $e) {
                return null;
            }
        }
        return $this->order;
    }

    /**
     * Process capture response
     * @param array <mixed> $result
     * @param Order $order
     * @return void
     * @throws LocalizedException
     */
    public function processCaptureResponse($result, $order)
    {
        if ($result
            && $result['response']['status']['code'] == self::CHARGED
            && $result['response']['status']['reason'] == self::DEBITED
            && $result['status'] == 200
        ) {
            $payment = $order->getPayment();
            if ($payment) {
                $additionalInformation = $payment->getAdditionalInformation();
                $additionalInformation = array_merge($additionalInformation, ['payoneerCapture' => 'success']);
                $payment->setAdditionalInformation($additionalInformation);
            }

            if ($order->canInvoice()) {
                $this->generateInvoice($order);
            }

            $this->showSuccessMessage('Payoneer capture transaction has been completed successfully.');
        } else {
            $this->showErrorMessage(__('Payoneer capture transaction failed. Check the payoneer.log for details.'));
        }
    }

    /**
     * Generates invoice
     * @param Order $order
     * @return void
     * @throws LocalizedException
     */
    public function generateInvoice($order)
    {
        if (!$order->getEntityId()) {
            throw new LocalizedException(__('The order no longer exists.'));
        }

        if (!$order->canInvoice()) {
            throw new LocalizedException(
                __('The order does not allow an invoice to be created.')
            );
        }
        try {
            $invoice = $this->invoiceService->prepareInvoice($order);
            if (!$invoice->getTotalQty()) {
                throw new LocalizedException(
                    __('You can\'t create an invoice without products.')
                );
            }
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(0);
            $invoice->getOrder()->setIsInProcess(true);

            $transactionSave =
                $this->transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transactionSave->save();

            try {
                $this->invoiceSender->send($invoice);
                $order->addCommentToStatusHistory(
                    __('Notified customer about invoice creation #%1.', $invoice->getId())
                )->setIsCustomerNotified(1);
            } catch (\Exception $e) {
                $this->showErrorMessage(__('We can\'t send the invoice email right now.'));
            }
        } catch (\Exception $e) {
            $this->showSuccessMessage(__('Something when wrong while generating invoice.', $e));
        }
    }

    /**
     * Check if Payoneer gateway is enabled
     * @return mixed|null
     */
    public function isPayoneerEnabled()
    {
        return $this->config->isPayoneerEnabled();
    }

    /**
     * Show success message
     * @param string $message
     * @return void
     */
    public function showSuccessMessage($message)
    {
        $this->messageManager
            ->addSuccessMessage(__($message));
    }

    /**
     * Show error message
     * @param string $message
     * @return void
     */
    public function showErrorMessage($message)
    {
        $this->messageManager
            ->addErrorMessage(__($message));
    }
}
