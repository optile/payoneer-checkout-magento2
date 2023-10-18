<?php

namespace Payoneer\OpenPaymentGateway\Controller\Integration;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\ListUpdateTransactionService;
use Payoneer\OpenPaymentGateway\Model\TransactionService;

/**
 * Class ProcessPayment
 * Process List request for hosted payment
 */
class ProcessPayment implements ActionInterface
{
    const LIST_EXPIRED = 'list_expired';
    const HOSTED = 'hosted';

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var TransactionService
     */
    private $transactionService;

    /**
     * @var ListUpdateTransactionService
     */
    private $updateTransactionService;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * ProcessPayment constructor.
     * @param Session $checkoutSession
     * @param TransactionService $transactionService
     * @param ListUpdateTransactionService $updateTransactionService
     * @param ManagerInterface $messageManager
     * @param Request $request
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Session $checkoutSession,
        TransactionService $transactionService,
        ListUpdateTransactionService $updateTransactionService,
        ManagerInterface $messageManager,
        Request $request,
        JsonFactory $resultJsonFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->transactionService = $transactionService;
        $this->updateTransactionService = $updateTransactionService;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
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
        $payment = $quote->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        /** @phpstan-ignore-next-line */
        $listId = isset($additionalInformation[Config::LIST_ID]) ?? $additionalInformation[Config::LIST_ID];
        try {
            $address = $this->request->getParam('address');
            $address = $address ? json_decode($address, true): null;
            $shipAddress = $this->request->getParam('shipAddress');
            $shipAddress = $shipAddress ? json_decode($shipAddress, true): null;

            if (!$listId) {
                $response = $this->transactionService->process($quote, $address, $shipAddress);
            } else {
                /** @var array <mixed> $response */
                $response = $this->updateTransactionService->process($quote->getPayment(),
                    Config::LIST_UPDATE, $address, $shipAddress);

                //if list session gave an update error, create a new one
                if ($this->updateError($response)) {
                    $response = $this->transactionService->process($quote, $address, $shipAddress);
                }
            }
            if($this->hidePayment($response))
            {
                $jsonData = [
                    'hidePayment' => true,
                    'status' => $response['response']['status'] ?? '',
                    'resultInfo' => $response['response']['resultInfo'] ?? ''
                ];
            }
            else if ($this->isHostedIntegration()) {
                $jsonData = $this->processHostedResponse($response);
            } else {
                $jsonData = $this->processEmbeddedResponse($response);
            }

            return $this->resultJsonFactory->create()->setData($jsonData);
        } catch (Exception $e) {
            return $this->resultJsonFactory->create()->setHttpResponseCode(400)->setData([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param array <mixed> $result
     * @return bool
     */
    public function isListExpired($result)
    {
        if (isset($result['reason']) && str_contains($result['reason'], self::LIST_EXPIRED)) {
            return true;
        }
        return false;
    }

    /**
     * @param array <mixed> $result
     * @return bool
     */
    public function updateError($result)
    {
        if (isset($result['status']) && ($result['status'] == 422 || $result['status'] == 409)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Process response of hosted integration
     * @param ResultInterface|null|bool|array <mixed> $result
     * @return array <mixed>
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function processHostedResponse($result)
    {
        if ($result && isset($result['response']['redirect'])) {
            $redirectURL =  $result['response']['redirect']['url'];
        } else {
            $quote = $this->checkoutSession->getQuote();
            $payment = $quote->getPayment();
            $additionalInformation = $payment->getAdditionalInformation();
            if (isset($additionalInformation[Config::REDIRECT_URL])) {
                $redirectURL = $additionalInformation[Config::REDIRECT_URL];
            } else {
                $this->messageManager->addErrorMessage(__('We couldn\'t process the payment'));
            }
        }
        return (isset($redirectURL)? ['redirectURL' => $redirectURL]: []);
    }

    /**
     * Process response of embedded integration
     * @param ResultInterface|null|bool|array <mixed> $result
     * @return array <mixed>
     */
    public function processEmbeddedResponse($result)
    {
        $jsonData = [];
        if ($result && isset($result['response']['links'])) {
            $jsonData = [
                'links' => $result['response']['links']
            ];
        } else {
            $this->messageManager->addErrorMessage(__('We couldn\'t process the payment'));
        }
        return $jsonData;
    }

    /**
     * @return bool
     */
    public function isHostedIntegration()
    {
        return $this->request->getParam(Config::INTEGRATION) == Config::INTEGRATION_HOSTED;
    }

    /**
     * Based on the response status, hide or show the Payment Method (for MoR)
     * @param mixed $response <mixed> $response
     * @return bool
     */
    public function hidePayment(mixed $response): bool
    {
        $result = true;

        if (is_array($response)
            && isset($response['response']['status']['code'])
            && $response['response']['status']['code'] == 'listed') {
            $result = false;
        }

        return $result;
    }
}
