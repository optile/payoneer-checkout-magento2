<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use PayPal\Braintree\Gateway\Helper\SubjectReader;

/**
 * Class ResponseHandler
 * Payoneer Response Handler
 */
class ResponseHandler implements HandlerInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * ResponseHandler constructor.
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        /** @var Payment $orderPayment */
        $orderPayment = $paymentDO->getPayment();

        $orderPayment->setTransactionId($response['response'][Config::TXN_ID]);
        $orderPayment->setIsTransactionClosed(false);
    }
}
