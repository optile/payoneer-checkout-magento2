<?php

namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderPaymentRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\CollectionFactory as OrderTransactionCollectionFactory;
use Payoneer\OpenPaymentGateway\Gateway\Http\Client\Client;
use Payoneer\OpenPaymentGateway\Gateway\Response\PayoneerResponseHandler;
use Payoneer\OpenPaymentGateway\Gateway\Validator\ResponseValidator;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Helper;
use Payoneer\OpenPaymentGateway\Model\Creditmemo\CreditmemoCreator;

/**
 * TransactionOrderUpdater class
 *
 * Class will handle the order update based on the response
 * from payoneer side during the notification processing via
 * cron job or after the fetch operation from admin side.
 */
class TransactionOrderUpdater
{
    const PRE_AUTHORIZED_STATUS = 'preauthorized';

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderTransactionCollectionFactory
     */
    protected $orderTransactionCollectionFactory;

    /**
     * @var BuilderInterface
     */
    protected $paymentTransactionBuilder;

    /**
     * @var Helper
     */
    protected $adminHelper;

    /**
     * @var CreditmemoCreator
     */
    protected $creditmemoCreator;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManager;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * @var OrderPaymentRepositoryInterface
     */
    protected $orderPaymentRepository;

    /**
     * TransactionOrderUpdater construct function
     *
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderTransactionCollectionFactory $orderTransactionCollectionFactory
     * @param BuilderInterface $paymentTransactionBuilder
     * @param Helper $adminHelper
     * @param CreditmemoCreator $creditmemoCreator
     * @param OrderManagementInterface $orderManager
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteria
     * @param TransactionRepositoryInterface $transactionRepository
     * @param OrderPaymentRepositoryInterface $orderPaymentRepository
     * @return void
     */
    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        OrderTransactionCollectionFactory $orderTransactionCollectionFactory,
        BuilderInterface $paymentTransactionBuilder,
        Helper $adminHelper,
        CreditmemoCreator $creditmemoCreator,
        OrderManagementInterface $orderManager,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteria,
        TransactionRepositoryInterface $transactionRepository,
        OrderPaymentRepositoryInterface $orderPaymentRepository
    ) {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->orderTransactionCollectionFactory = $orderTransactionCollectionFactory;
        $this->paymentTransactionBuilder = $paymentTransactionBuilder;
        $this->adminHelper = $adminHelper;
        $this->creditmemoCreator = $creditmemoCreator;
        $this->orderManager = $orderManager;
        $this->orderRepository = $orderRepository;
        $this->searchCriteria = $searchCriteria;
        $this->transactionRepository = $transactionRepository;
        $this->orderPaymentRepository = $orderPaymentRepository;
    }

    /**
     * Process the response got from the notification.
     *
     * @param string $orderId
     * @param array <mixed> $response
     * @return bool|void
     * @throws LocalizedException
     */
    public function processNotificationResponse($orderId, $response)
    {
        $filteredResponse = [];
        $filteredResponse['transaction_id'] = $response['transactionId'];
        $filteredResponse['status_code'] = $response['statusCode'];
        $filteredResponse['reason_code'] = $response['reasonCode'];
        $filteredResponse['long_id'] = $response['longId'];

        return $this->processResponse($orderId, $filteredResponse);
    }

    /**
     * Process the response got from the fetch operation
     *
     * @param string|Order $order
     * @param array <mixed> $response
     * @return bool|void
     * @throws LocalizedException
     */
    public function processFetchUpdateResponse($order, $response)
    {
        $actualResponse = $response['response'];
        $filteredResponse = [];
        $filteredResponse['transaction_id'] = $actualResponse['identification']['transactionId'];
        $filteredResponse['status_code'] = $actualResponse['status']['code'];
        $filteredResponse['reason_code'] = $actualResponse['status']['reason'];
        $filteredResponse['long_id'] = $actualResponse['identification']['longId'];

        return $this->processResponse($order, $filteredResponse);
    }

    /**
     * Check the response status code and reason and based
     * on the values do the corresponding actions.
     *
     * @param string|Order $order
     * @param array <mixed> $response
     * @return bool|void
     * @throws LocalizedException
     */
    public function processResponse($order, $response)
    {
        switch ([$response['status_code'], $response['reason_code']]) {
            case [self::PRE_AUTHORIZED_STATUS, self::PRE_AUTHORIZED_STATUS]:
                return $this->checkAndAuthorizeOrder($order, $response);
            case [Helper::CHARGED, Helper::DEBITED]:
            case [Helper::CHARGED, Helper::CAPTURE_CLOSED]:
                return $this->checkAndCaptureOrder($order, $response);
            case [ResponseValidator::REFUND_PAID_OUT_STATUS, ResponseValidator::REFUND_CREDITED]:
            case [ResponseValidator::REFUND_PAID_OUT_STATUS, ResponseValidator::REFUND_PAID_OUT_STATUS]:
                return $this->checkAndRefundOrder($order, $response);
            case [ResponseValidator::AUTH_CANCEL_PENDING_STATUS, ResponseValidator::CANCELLATION_REQUESTED]:
                return $this->checkAndVoidOrder($order, $response);
            case [ResponseValidator::AUTH_CANCELLED_STATUS, ResponseValidator::PREAUTHORIZATION_CANCELLED]:
                return $this->checkAndVoidOrderOnPreAuthCancel($order, $response);
        }
    }

    /**
     * Authorize the order if it's not already authorized.
     *
     * @param string|Order $order
     * @param array <mixed> $response
     * @return bool|void
     * @throws LocalizedException
     */
    public function checkAndAuthorizeOrder($order, $response)
    {
        try {
            $orderObj = $this->getOrder($order);
            $authTxn = $this->getTransaction($orderObj->getId(), Helper::AUTHORIZATION);
            if ($authTxn != null && $authTxn->getTransactionId()) {
                return $this->changeOrderToProcessing($orderObj);
            }
            $orderTotal = $orderObj->getBaseCurrency()->formatTxt(
                $orderObj->getGrandTotal()
            );
            $txnData = [
                'additional_info' => $response,
                'additional_info_key' => 'auth_response',
                'is_transaction_closed' => false,
                'transaction_type' => Helper::AUTHORIZATION,
                'order_comment' => __('Authorized amount of %1.', $orderTotal),
                'parent_txn_id' => null
            ];

            $this->addNewTransactionEntry(
                $orderObj,
                $txnData
            );
        } catch (LocalizedException $le) {
            throw new LocalizedException(
                __($le->getMessage())
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Something went wrong while authorizing the order.')
            );
        }
    }

    /**
     * Refund the order if it's not already refunded.
     *
     * @param string|Order $order
     * @param array <mixed> $response
     * @return bool|void
     * @throws LocalizedException
     */
    public function checkAndRefundOrder($order, $response)
    {
        try {
            $orderObj = $this->getOrder($order);
            $authTxn = $this->getTransaction($orderObj->getId(), Client::REFUND);
            if ($authTxn != null && $authTxn->getTransactionId()) {
                if ($orderObj->getState() != Order::STATE_CLOSED) {
                    $this->creditmemoCreator->create($orderObj);
                }
                return true;
            }

            $this->creditmemoCreator->create($orderObj);

            $orderTotal = $orderObj->getBaseCurrency()->formatTxt(
                $orderObj->getGrandTotal()
            );
            $txnData = [
                'additional_info' => $response,
                'additional_info_key' => PayoneerResponseHandler::ADDITIONAL_INFO_KEY_REFUND_RESPONSE,
                'is_transaction_closed' => true,
                'transaction_type' => Client::REFUND,
                'order_comment' => __('Refunded amount of %1.', $orderTotal),
                'parent_txn_id' => $response['transaction_id'],
                'txn_id_post_text' => 'refund'
            ];
            $this->addNewTransactionEntry(
                $orderObj,
                $txnData
            );
        } catch (LocalizedException $le) {
            throw new LocalizedException(
                __($le->getMessage())
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Something went wrong while refunding the order.')
            );
        }
    }

    /**
     * Cancel the auth if not already cancelled.
     *
     * @param string|Order $order
     * @param array <mixed> $response
     * @return bool|void
     * @throws LocalizedException
     */
    public function checkAndVoidOrder($order, $response)
    {
        try {
            $orderObj = $this->getOrder($order);
            $authTxn = $this->getTransaction($orderObj->getId(), Client::VOID);
            if ($authTxn != null && $authTxn->getTransactionId()) {
                if ($orderObj->getState() != Order::STATE_CANCELED) {
                    $this->orderManager->cancel($orderObj->getId());
                }
                return true;
            }
            $this->cancelOrderAndAddNewVoidTransaction(
                $orderObj,
                $response
            );
        } catch (LocalizedException $le) {
            throw new LocalizedException(
                __($le->getMessage())
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Something went wrong while cancelling the authorization.' . $e->getMessage())
            );
        }
    }

    /**
     * Cancel the auth if not already cancelled.
     *
     * @param string|Order $order
     * @param array <mixed> $response
     * @return bool|void
     * @throws LocalizedException
     */
    public function checkAndVoidOrderOnPreAuthCancel($order, $response)
    {
        try {
            $orderObj = $this->getOrder($order);
            $authTxn = $this->getTransaction($orderObj->getId(), Client::VOID);
            if ($authTxn != null && $authTxn->getTransactionId()) {
                if ($orderObj->getState() != Order::STATE_CANCELED) {
                    $this->orderManager->cancel($orderObj->getId());
                }
                $authTxn->unsAdditionalInformation();
                $authTxn->setAdditionalInformation(
                    Transaction::RAW_DETAILS,
                    $response
                );
                $this->transactionRepository->save($authTxn);
                $orderObj->addCommentToStatusHistory(
                    __('Payoneer status changed to preauthorization_canceled.')
                );
                $this->orderRepository->save($orderObj);

                return true;
            }
            $this->cancelOrderAndAddNewVoidTransaction(
                $orderObj,
                $response
            );
        } catch (LocalizedException $le) {
            throw new LocalizedException(
                __($le->getMessage())
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Something went wrong while cancelling the preauthorization.')
            );
        }
    }

    /**
     * Cancel the order and add new void transaction entry.
     *
     * @param Order $orderObj
     * @param array <mixed> $response
     * @return bool
     * @throws LocalizedException
     */
    private function cancelOrderAndAddNewVoidTransaction($orderObj, $response)
    {
        $this->setAdditionalInformation($orderObj, 'payoneerCancel');

        $this->orderManager->cancel($orderObj->getId());

        $orderTotal = $orderObj->getBaseCurrency()->formatTxt(
            $orderObj->getGrandTotal()
        );
        $txnData = [
            'additional_info' => $response,
            'additional_info_key' => PayoneerResponseHandler::ADDITIONAL_INFO_KEY_AUTH_CANCEL_RESPONSE,
            'is_transaction_closed' => true,
            'transaction_type' => Client::VOID,
            'order_comment' => __('Void amount of %1.', $orderTotal),
            'parent_txn_id' => $response['transaction_id'],
            'txn_id_post_text' => 'void'
        ];
        $this->addNewTransactionEntry(
            $orderObj,
            $txnData
        );

        return true;
    }

    /**
     * @param Order $orderObj
     * @param string $transactionType
     * @return void
     */
    public function setAdditionalInformation($orderObj, $transactionType)
    {
        $payment = $orderObj->getPayment();
        if ($payment) {
            $additionalInformation = $payment->getAdditionalInformation();
            $additionalInformation = array_merge($additionalInformation, [$transactionType => 'success']);
            $payment->setAdditionalInformation($additionalInformation);
        }
    }

    /**
     * Capture the order if it's not already captured.
     *
     * @param string|Order $order
     * @param array <mixed> $response
     * @return bool|void
     * @throws LocalizedException
     */
    public function checkAndCaptureOrder($order, $response)
    {
        try {
            $orderObj = $this->getOrder($order);
            $authTxn = $this->getTransaction($orderObj->getId(), Helper::CAPTURE);
            if ($authTxn != null && $authTxn->getTransactionId()) {
                return $this->changeOrderToProcessing($orderObj);
            }

            $this->setAdditionalInformation($orderObj, 'payoneerCapture');

            if ($orderObj->canInvoice()) {
                $this->adminHelper->generateInvoice($orderObj);
            }
        } catch (LocalizedException $le) {
            throw new LocalizedException(
                __($le->getMessage())
            );
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Something went wrong while capturing the order.')
            );
        }
    }

    /**
     * Get the order. If its an increment id then the coresponding
     * model is loaded with the id and returned.
     *
     * @param Order|string $order
     * @return false|mixed
     * @throws LocalizedException
     */
    private function getOrder($order)
    {
        if (is_object($order)) {
            return $order;
        }
        $criteria = $this->searchCriteria->addFilter(OrderInterface::INCREMENT_ID, $order)->create();
        $orders = $this->orderRepository->getList($criteria)->getItems();
        if (count($orders)) {
            return reset($orders);
        }
        throw new LocalizedException(
            __('No such order with increment id %s exist. ', $order)
        );
    }

    /**
     * Get the transaction from the payment transaction collection
     * matching the conditions.
     *
     * @param int $orderId
     * @param string $txnType
     * @param int $txnId
     * @return Transaction|null
     */
    private function getTransaction($orderId, $txnType = null, $txnId = null)
    {
        $transaction = null;
        $collection = $this->orderTransactionCollectionFactory->create();
        $collection->addOrderIdFilter($orderId);
        if (!empty($txnType)) {
            $collection->addTxnTypeFilter($txnType);
        }
        if (!empty($txnId)) {
            $collection->addFieldToFilter('transaction_id', ['eq' => $txnId]);
        }
        if ($collection->getSize()) {
            /** @var  Transaction $transaction */
            $transaction = $collection->getFirstItem();
        }
        return $transaction;
    }

    /**
     * Add new payment transaction corresponding to the
     * operation.
     *
     * @param Order $order
     * @param array <mixed> $data
     * @return void
     * @throws LocalizedException
     */
    private function addNewTransactionEntry($order, $data)
    {
        try {
            /** @var Payment $payment */
            $payment = $order->getPayment();
            $payment->setLastTransId($data['additional_info']['transaction_id']);
            $payment->setTransactionId($data['additional_info']['transaction_id']);
            $payment->setAdditionalInformation(
                $data['additional_info_key'],
                $data['additional_info']
            );
            $payment->setIsTransactionClosed($data['is_transaction_closed']);
            /** @var Transaction $transaction */
            $transaction = $this->buildTransactionObject(
                $order,
                $payment,
                $data
            );

            $payment->addTransactionCommentsToOrder(
                $transaction,
                $data['order_comment']
            );
            $payment->setParentTransactionId($data['parent_txn_id']);

            $this->orderPaymentRepository->save($payment);

            $this->orderRepository->save($order);

            $this->transactionRepository->save($transaction);
            return;
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Something went wrong while creating new %1 transaction entry.', $data['transaction_type'])
            );
        }
    }

    /**
     * Build the transaction object with the corresponding
     * configurations.
     *
     * @param Order $order
     * @param Order\Payment|OrderPaymentInterface $payment
     * @param array <mixed> $data
     * @return Transaction|TransactionInterface
     */
    private function buildTransactionObject($order, $payment, $data)
    {
        $txnId = $this->getFinalTransactionId($data);
        return $this->paymentTransactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($txnId)
            ->setAdditionalInformation(
                [Transaction::RAW_DETAILS => $data['additional_info']]
            )->setFailSafe(true)
            ->build($data['transaction_type']);
    }

    /**
     * Change the order status to processing.
     *
     * @param Order $order
     * @return bool
     */
    private function changeOrderToProcessing($order)
    {
        if ($order->getStatus() == Order::STATE_PROCESSING) {
            return true;
        }
        $order->setStatus(Order::STATE_PROCESSING);
        $this->orderRepository->save($order);
        return true;
    }

    /**
     * Get the final txn id for the payment transaction
     * table.
     *
     * @param array <mixed> $data
     * @return string
     */
    private function getFinalTransactionId($data)
    {
        $txnId = $data['additional_info']['transaction_id'];
        if (!empty($data['txn_id_post_text'])) {
            return $txnId . '-' . $data['txn_id_post_text'];
        }
        return $txnId;
    }
}