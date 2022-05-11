<?php

namespace Payoneer\OpenPaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\Helper as AdminHelper;
use Payoneer\OpenPaymentGateway\Model\TransactionService;

/**
 * CancelOrderObserver class
 *
 * Class will handle the payoneer side cancellation
 * of the auth request if already created.
 */
class CancelOrderObserver implements ObserverInterface
{
    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * @var AdminHelper
     */
    protected $payoneerAdminHelper;

    /**
     * CancelOrderObserver construct function
     *
     * @param AdminHelper $payoneerAdminHelper
     * @param TransactionService $transactionService
     *
     * @return void
     */
    public function __construct(
        TransactionService $transactionService,
        AdminHelper $payoneerAdminHelper
    ) {
        $this->transactionService = $transactionService;
        $this->payoneerAdminHelper = $payoneerAdminHelper;
    }

    /**
     * Cancel authorization.
     *
     * @param Observer $observer
     * @return ResultInterface|void|null|array <mixed>
     * @throws NotFoundException
     * @throws CommandException
     */
    public function execute(Observer $observer)
    {
        if ($this->payoneerAdminHelper->isPayoneerEnabled()) {
            $cancellationDone = false;
            /**@var Order $order */
            $order = $observer->getEvent()->getOrder();
            $additionalInformation = $order->getPayment()->getAdditionalInformation();
            if ($additionalInformation && isset($additionalInformation['payoneerCancel'])) {
                $cancellationDone = true;
            }
            if (!$cancellationDone) {
                if ($order->getState() == 'canceled' && $this->payoneerAdminHelper->canCancelAuthorization($order)) {
                    return $this->transactionService->processAuthCancel($order);
                }
            }
        }
    }
}
