<?php

namespace Payoneer\OpenPaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Helper;
use Payoneer\OpenPaymentGateway\Model\ListCaptureTransactionService;

/**
 * Class InvoiceSaveAfterObserver
 * Call PayoneerCapture after invoice creation
 */
class InvoiceSaveAfterObserver implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var ListCaptureTransactionService
     */
    protected $listCapture;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Capture constructor.
     * @param ListCaptureTransactionService $listCapture
     * @param Helper $helper
     */
    public function __construct(
        ListCaptureTransactionService $listCapture,
        Helper $helper
    ) {
        $this->listCapture = $listCapture;
        $this->helper=$helper;
    }

    /**
     * Call Payoneer capture process if not yet captured
     * used for event: sales_order_invoice_save_after
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        if ($this->helper->isPayoneerEnabled()) {
            $invoice = $observer->getEvent()->getInvoice();
            /**@var Order $order */
            $order = $invoice->getOrder();

            $additionalInformation = $order->getPayment()->getAdditionalInformation();

            if (!isset($additionalInformation['payoneerCapture'])) {
                $this->listCapture->process($order);
            }
        }
        return $this;
    }
}
