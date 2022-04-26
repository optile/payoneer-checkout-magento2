<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Exception;
use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Magento\Quote\Api\CartRepositoryInterface;
use Payoneer\OpenPaymentGateway\Gateway\Http\Client\Client;
use Magento\Payment\Model\InfoInterface;

/**
 * Class GetPayoneerTransactionService
 *
 * Process List Api Request
 */
class TransactionService
{
    const HOSTED = 'hosted';
    /**
     * @var CommandPoolInterface
     */
    protected $commandPool;

    /**
     * @var PaymentDataObjectFactory
     */
    protected $paymentDataObjectFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * GetPayoneerTransactionService constructor.
     * @param CommandPoolInterface $commandPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param JsonFactory $resultJsonFactory
     * @param ConfigInterface $config
     * @param ManagerInterface $messageManager
     * @param Request $request
     * @param CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        JsonFactory $resultJsonFactory,
        ConfigInterface $config,
        ManagerInterface $messageManager,
        Request $request,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->commandPool = $commandPool;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->messageManager = $messageManager;
        $this->request = $request;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * Process Api request
     *
     * @param Quote $quote
     * @return Json | array <mixed>
     * @throws Exception
     */
    public function process(Quote $quote)
    {
        if (!$this->config->getValue('active')) {
            return [];
        }

        if (!$quote->getReservedOrderId()) {
            $quote = $quote->reserveOrderId();
            $this->quoteRepository->save($quote);
        }

        $token = strtotime('now') . uniqid();
        $nToken = strtotime('now') . uniqid();

        $payment = $quote->getPayment();
        $transactionId = $payment->getId() . strtotime('now');
        $payment->setAdditionalInformation(Config::TOKEN, $token);
        $payment->setAdditionalInformation(Config::TOKEN_NOTIFICATION, $nToken);
        $payment->setAdditionalInformation(Config::TXN_ID, $transactionId);

        $quote->setPayment($payment);
        $this->quoteRepository->save($quote);

        $paymentDataObject = $this->paymentDataObjectFactory->create($payment);
        try {
            $integration = $this->request->getParam('integration');
            $address = $this->request->getParam('address');
            $address = json_decode($address, true);

            $isHostedIntegration = $integration == self::HOSTED;
            /** @var array <mixed> $result */
            $result = $this->commandPool->get($integration)->execute([
                'payment' => $paymentDataObject,
                'amount' => $quote->getGrandTotal(),
                'address' => $address
            ]);

            if ($isHostedIntegration) {
                $jsonData = $this->processHostedResponse($result);
            } else {
                $jsonData = $this->processEmbeddedResponse($result);
            }

            return $this->resultJsonFactory->create()->setData($jsonData);
        } catch (Exception $e) {
            return $this->resultJsonFactory->create()->setHttpResponseCode(400)->setData([
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Process response of hosted integration
     * @param array <mixed> $result
     * @return array <mixed>
     */
    public function processHostedResponse($result)
    {
        $jsonData = [];
        if ($result && isset($result['response']['redirect'])) {
            $jsonData = [
                'redirectURL' => $result['response']['redirect']['url']
            ];
        } else {
            $this->messageManager->addErrorMessage(__('Something went wrong while processing payment.'));
        }
        return $jsonData;
    }

    /**
     * Process response of embedded integration
     * @param array <mixed> $result
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
            $this->messageManager->addErrorMessage(__('Something went wrong while processing payment.'));
        }
        return $jsonData;
    }

    /**
     * Initiate the authorization cancellation request.
     *
     * @param Order $order
     * @return ResultInterface|void|null|array <mixed>
     * @throws NotFoundException
     * @throws CommandException
     */
    public function processAuthCancel($order)
    {
        /** @var InfoInterface $payment */
        $payment = $order->getPayment();
        $paymentDataObject = $this->paymentDataObjectFactory->create($payment);

        return $this->commandPool->get(Client::AUTHORIZATION_CANCEL)->execute([
            'payment' => $paymentDataObject,
            'order' => $order
        ]);
    }
}
