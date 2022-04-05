<?php

namespace Payoneer\OpenPaymentGateway\Controller\Adminhtml\Gateway;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Helper;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\TransactionService as AdminTransactionService;

/**
 * Class Capture
 *
 * Process Payoneer Capture request
 */
class Capture extends Action
{
    /**
     * @var AdminTransactionService
     */
    protected $listCapture;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Capture constructor.
     *
     * @param Action\Context $context
     * @param AdminTransactionService $listCapture
     * @param Helper $helper
     */
    public function __construct(
        Action\Context $context,
        AdminTransactionService $listCapture,
        Helper $helper
    ) {
        parent::__construct($context);
        $this->listCapture = $listCapture;
        $this->helper = $helper;
    }

    /**
     * Process Payoneer capture
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
                /** @var string[] $result */
                $result = $this->listCapture->process($order, Config::LIST_CAPTURE);

                if ($result) {
                    $this->helper->processCaptureResponse($result, $order);
                }
            } catch (\Exception $e) {
                $this->helper
                    ->showErrorMessage(__('Something went wrong with the transaction. ' . $e->getMessage()));
            }
        }
        return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }
}
