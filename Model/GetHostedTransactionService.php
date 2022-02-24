<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Quote\Model\Quote;

class GetHostedTransactionService
{
    const COMMAND_GET_LIST = 'hosted';

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
     * GetHostedTransactionService constructor.
     * @param CommandPoolInterface $commandPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param JsonFactory $resultJsonFactory
     * @param ConfigInterface $config
     * @param ManagerInterface $messageManager
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        JsonFactory $resultJsonFactory,
        ConfigInterface $config,
        ManagerInterface $messageManager
    ) {
        $this->commandPool = $commandPool;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
        $this->messageManager = $messageManager;
    }

    /**
     * Process Api request
     *
     * @param Quote $quote
     * @return Json | array <mixed>
     * @throws \Exception
     */
    public function process(Quote $quote)
    {
        if (!$this->config->getValue('active')) {
            return [];
        }

        if (!$quote->getReservedOrderId()) {
            $quote->reserveOrderId()->save();
        }

        $jsonData = [];
        $token = strtotime('now') . uniqid();

        $payment = $quote->getPayment();
        $payment->setAdditionalInformation('token', $token);
        $payment->save();

        $paymentDataObject = $this->paymentDataObjectFactory->create($payment);
        try {
            $result = $this->commandPool->get(self::COMMAND_GET_LIST)->execute([
                'payment' => $paymentDataObject,
                'amount' => $quote->getGrandTotal()
            ]);

            if (isset($result['response']['redirect'])) {
                $jsonData = [
                    'redirectURL' => $result['response']['redirect']['url']
                ];
            } else {
                $this->messageManager->addErrorMessage(__('Something went wrong while processing payment.'));
            }

            return $this->resultJsonFactory->create()->setData($jsonData);
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setHttpResponseCode(400)->setData([
                'error' => $e->getMessage()
            ]);
        }
    }
}
