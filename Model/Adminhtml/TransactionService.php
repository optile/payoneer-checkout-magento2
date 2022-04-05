<?php
namespace Payoneer\OpenPaymentGateway\Model\Adminhtml;

use Exception;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Sales\Model\Order;

/**
 * Class TransactionService
 *
 * Process admin transactions api requests
 */
class TransactionService
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
     * TransactionService constructor.
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
     * @param string $command
     * @return ResultInterface|null|bool|array <mixed>
     */
    public function process(Order $order, $command)
    {
        $payment = $order->getPayment();
        if ($payment) {
            try {
                $paymentDataObject = $this->paymentDataObjectFactory->create($payment);
                return $this->commandPool->get($command)->execute([
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
