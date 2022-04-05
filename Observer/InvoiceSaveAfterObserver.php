<?php

namespace Payoneer\OpenPaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Helper;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\TransactionService;

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
     * @var TransactionService
     */
    protected $listCapture;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * InvoiceSaveAfterObserver constructor.
     * @param TransactionService $listCapture
     * @param Helper $helper
     */
    public function __construct(
        TransactionService $listCapture,
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
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        if ($this->helper->isPayoneerEnabled()) {
            $invoice = $observer->getEvent()->getInvoice();
            /**@var Order $order */
            $order = $invoice->getOrder();

            $additionalInformation = $order->getPayment()->getAdditionalInformation();

            if (!isset($additionalInformation['payoneerCapture'])) {
                $result = $this->listCapture->process($order, Config::LIST_CAPTURE);
                if ($result && is_array($result)) {
                    $result = $this->listCapture->process($order, Config::LIST_CAPTURE);
                    if ($result) {
                        /** @phpstan-ignore-next-line */
                        $this->helper->processCaptureResponse($result, $order);
                    }
                }
            }
        }
        return $this;
    }
}
