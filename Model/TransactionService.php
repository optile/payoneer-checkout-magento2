<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Exception;
use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Model\Quote;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Magento\Quote\Api\CartRepositoryInterface;

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

        $payment = $quote->getPayment();
        $transactionId = $payment->getId() . strtotime('now');
        $payment->setAdditionalInformation(Config::TOKEN, $token);
        $payment->setAdditionalInformation(Config::TXN_ID, $transactionId);
        $payment->save();

        $paymentDataObject = $this->paymentDataObjectFactory->create($payment);
        try {
            $integration = $this->request->getParam('integration');
            $address = $this->request->getParam('address');
            $address = json_decode($address, true);

            $isHostedIntegration = $integration == self::HOSTED;
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
     * @param array <mixed>|ResultInterface|null $result
     * @return array <mixed>
     */
    public function processHostedResponse($result)
    {
        $jsonData = [];
        /** @phpstan-ignore-next-line */
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
     * @param array <mixed>|ResultInterface|null $result
     * @return array <mixed>
     */
    public function processEmbeddedResponse($result)
    {
        $jsonData = [];
        /** @phpstan-ignore-next-line */
        if ($result && isset($result['response']['links'])) {
            $jsonData = [
                'links' => $result['response']['links']
            ];
        } else {
            $this->messageManager->addErrorMessage(__('Something went wrong while processing payment.'));
        }
        return $jsonData;
    }
}
