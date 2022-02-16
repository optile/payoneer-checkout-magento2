<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
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
     * GetHostedTransactionService constructor.
     * @param CommandPoolInterface $commandPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param JsonFactory $resultJsonFactory
     * @param ConfigInterface $config
     */
    public function __construct(
        CommandPoolInterface $commandPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        JsonFactory $resultJsonFactory,
        ConfigInterface $config
    ) {
        $this->commandPool = $commandPool;
        $this->paymentDataObjectFactory = $paymentDataObjectFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->config = $config;
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
        $payment = $quote->getPayment();

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
            }

            return $this->resultJsonFactory->create()->setData($jsonData);
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setHttpResponseCode(400)->setData([
                'error' => $e->getMessage()
            ]);
        }
    }
}
