<?php

namespace Payoneer\OpenPaymentGateway\Controller\Integration;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Payoneer\OpenPaymentGateway\Model\TransactionService;

/**
 * Class ProcessPayment
 * Process List request for hosted payment
 */
class ProcessPayment implements ActionInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * ProcessPayment constructor.
     * @param Session $checkoutSession
     * @param TransactionService $transactionService
     */
    public function __construct(
        Session $checkoutSession,
        TransactionService $transactionService
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->transactionService = $transactionService;
    }

    /**
     * @return Json | array <mixed>
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Exception
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        return $this->transactionService->process($quote);
    }
}
