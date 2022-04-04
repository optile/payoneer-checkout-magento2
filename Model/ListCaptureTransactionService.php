<?php

namespace Payoneer\OpenPaymentGateway\Model;

use Exception;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * Class ListCaptureTransactionService
 *
 * Process List capture api request
 */
class ListCaptureTransactionService
{
    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;

    /**
     * @var PaymentDataObjectFactory
     */
    protected $paymentDataObjectFactory;

    /**
     * ListCaptureTransactionService constructor.
     * @param CommandPoolInterface $commandPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        PaymentDataObjectFactory $paymentDataObjectFactory
    ) {
        $this->commandPool = $commandPool;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
    }

    /**
     * Process Api request
     *
     * @param Order $order
     * @return ResultInterface|null|bool
     */
    public function process($order)
    {
        /** @var InfoInterface $payment*/
        $payment = $order->getPayment();

        try {
            $paymentDataObject = $this->paymentDataObjectFactory->create($payment);
            return $this->commandPool->get(Config::LIST_CAPTURE)->execute([
                    'payment' => $paymentDataObject
                ]);
        } catch (Exception $e) {
            return false;
        }
    }
}
