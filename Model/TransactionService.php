<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Exception;
use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Payment;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Gateway\Http\Client\Client;

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
     * @return ResultInterface|null|bool|array <mixed>
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
        $nToken = $this->getNotificationToken();

        $payment = $quote->getPayment();
        $transactionId = $payment->getId() . strtotime('now');
        $payment->setAdditionalInformation(Config::TOKEN, $token);
        $payment->setAdditionalInformation(Config::TOKEN_NOTIFICATION, $nToken);
        $payment->setAdditionalInformation(Config::TXN_ID, $transactionId);

        $quote->setPayment($payment);
        $this->quoteRepository->save($quote);

        $paymentDataObject = $this->paymentDataObjectFactory->create($payment);
        try {
            $address = $this->request->getParam('address');
            $address = json_decode($address, true);

            /** @var ResultInterface $result */
            $result = $this->commandPool->get($this->getIntegration())->execute([
                'payment' => $paymentDataObject,
                'amount' => $quote->getGrandTotal(),
                'address' => $address,
                'totalItemsCount' => $quote->getItemsCount()
            ]);
            /** @phpstan-ignore-next-line */
            $this->setAdditionalInformation($quote, $result);

            return $result;
        } catch (Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
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

    /**
     * @return string
     * @throws Exception
     */
    public function getNotificationToken()
    {
        $bytes = random_bytes(20);
        return bin2hex($bytes);
    }

    /**
     * @param Quote $quote
     * @param array <mixed> $result
     * @return void
     * @throws LocalizedException
     */
    public function setAdditionalInformation($quote, $result)
    {
        $payment = $quote->getPayment();
        if (isset($result['response'])
            && isset($result['response']['identification'])
            && isset($result['response']['identification']['longId'])) {
            $listId = $result['response']['identification']['longId'];
            $payment->setAdditionalInformation(Config::LIST_ID, $listId);
            if ($this->getIntegration() == Config::INTEGRATION_HOSTED
                && isset($result['response']['redirect'])
                && isset($result['response']['redirect']['url'])) {
                    $payment->setAdditionalInformation(Config::REDIRECT_URL, $result['response']['redirect']['url']);
            }
            $this->saveQuote($quote, $payment);
        }
    }

    /**
     * @param Quote $quote
     * @param Payment $payment
     * @return void
     */
    public function saveQuote($quote, $payment)
    {
        $quote->setPayment($payment);
        //$this->quoteRepository->save($quote);//Gift cart amount is getting as null if quoterepository save is called
        $quote->save();/** @phpstan-ignore-line */
    }

    /**
     * Get integration type from request
     *
     * @return string
     */
    private function getIntegration()
    {
        return (string)$this->request->getParam(Config::INTEGRATION);
    }
}
