<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Model\Quote\Payment;

/**
 * Class ListUpdateTransactionService
 *
 * Process transactions api request for list update
 */
class ListUpdateTransactionService
{
    /**
     * @var CommandPoolInterface
     */
    private $commandPool;

    /**
     * @var PaymentDataObjectFactory
     */
    private $paymentDataObjectFactory;

    /**
     * ListUpdateTransactionService constructor.
     *
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
     * @param Payment $payment
     * @param string $command
     * @return ResultInterface|null|bool|array <mixed>
     * @throws LocalizedException
     */
    public function process(Payment $payment, $command)
    {
        try {
            /** @var InfoInterface $payment*/
            $paymentDataObject = $this->paymentDataObjectFactory->create($payment);

            return $this->commandPool->get($command)->execute([
                'payment' => $paymentDataObject,
                'amount' => $payment->getQuote()->getGrandTotal(), /** @phpstan-ignore-line */
                'totalItemsCount' => $payment->getQuote()->getItemsCount() /** @phpstan-ignore-line */
            ]);
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
