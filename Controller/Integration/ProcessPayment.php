<?php

namespace Payoneer\OpenPaymentGateway\Controller\Integration;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
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
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var TransactionService
     */
    protected $transactionService;

    /**
     * ProcessPayment constructor.
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     * @param TransactionService $transactionService
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        TransactionService $transactionService
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->transactionService = $transactionService;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
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
