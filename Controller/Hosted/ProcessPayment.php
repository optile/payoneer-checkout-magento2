<?php

namespace Payoneer\OpenPaymentGateway\Controller\Hosted;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Payoneer\OpenPaymentGateway\Model\Api\Request;
use Payoneer\OpenPaymentGateway\Model\GetHostedTransactionService;

/**
 * Class ProcessPayment
 * Process List request for hosted payment
 */
class ProcessPayment implements HttpGetActionInterface
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
     * @var GetHostedTransactionService
     */
    protected $hostedTransactionService;

    /**
     * ProcessPayment constructor.
     * @param JsonFactory $resultJsonFactory
     * @param Session $checkoutSession
     * @param GetHostedTransactionService $hostedTransactionService
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Session $checkoutSession,
        GetHostedTransactionService $hostedTransactionService
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->hostedTransactionService = $hostedTransactionService;
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $quote = $this->checkoutSession->getQuote();
        return $this->hostedTransactionService->process($quote);
    }
}
