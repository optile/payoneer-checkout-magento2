<?php
namespace Payoneer\OpenPaymentGateway\Model\Adminhtml;

use Exception;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Sales\Model\Order;
use Magento\Payment\Model\InfoInterface;

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
        $result = [];
        /** @var InfoInterface $payment*/
        $payment = $order->getPayment();

        try {
            $paymentDataObject = $this->paymentDataObjectFactory->create($payment);
            /** @var array <mixed> $result */
            $result = $this->commandPool->get($command)->execute([
                'payment' => $paymentDataObject
            ]);
            return $result;
        } catch (Exception $e) {
            return $result;
        }
    }
}
