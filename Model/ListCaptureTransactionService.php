<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Exception;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Sales\Model\Order;

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
    public function process(Order $order)
    {
        $payment = $order->getPayment();

        if ($payment) {
            try {
                $paymentDataObject = $this->paymentDataObjectFactory->create($payment);
                return $this->commandPool->get('list_capture')->execute([
                    'payment' => $paymentDataObject,
                    'amount' => $payment->getAmountAuthorized()
                ]);
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
}
