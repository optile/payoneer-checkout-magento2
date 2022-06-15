<?php

namespace Payoneer\OpenPaymentGateway\Controller\Adminhtml\Gateway;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Helper;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\TransactionService as AdminTransactionService;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\TransactionOrderUpdater;

/**
 * Class Fetch
 * Process Payoneer Fetch request
 */
class Fetch extends Action
{
    /**
     * @var AdminTransactionService
     */
    protected $transactionService;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var TransactionOrderUpdater
     */
    protected $transactionOrderUpdater;

    /**
     * Fetch constructor.
     *
     * @param Action\Context $context
     * @param AdminTransactionService $transactionService
     * @param TransactionOrderUpdater $transactionOrderUpdater
     * @param Helper $helper
     * @return void
     */
    public function __construct(
        Action\Context $context,
        AdminTransactionService $transactionService,
        TransactionOrderUpdater $transactionOrderUpdater,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->transactionService = $transactionService;
        $this->helper=$helper;
        $this->transactionOrderUpdater=$transactionOrderUpdater;
    }

    /**
     * Process Payoneer fetch
     *
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $orderId = (int)$this->getRequest()->getParam('order_id');
        if ($orderId) {
            try {
                /** @var Order $order */
                $order = $this->helper->getOrder($orderId);
                /** @var mixed $result */
                $result = $this->transactionService->process($order, Config::LIST_FETCH);
                if ($result && $result['status'] == 200) {
                    $this->transactionOrderUpdater->processFetchUpdateResponse(
                        $order,
                        $result
                    );
                    $this->helper->showSuccessMessage(
                        __('Successfully fetched and updated the data.')
                    );
                } else {
                    $this->helper
                        ->showErrorMessage(
                            __('Error response is received from Payoneer. Error status code: %1. Please check var/log/payoneer.log for more details.', $result['status'])
                        );
                }
            } catch (\Exception $e) {
                $this->helper
                    ->showErrorMessage(__('Something went wrong with the transaction.') . ' ' . $e->getMessage());
            }
        }
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }
}
