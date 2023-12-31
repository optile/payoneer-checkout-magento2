<?php

namespace Payoneer\OpenPaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment\Transaction;

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
     * @param array <mixed> $handlingSubject
     * @param array <mixed> $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = $this->subjectReader->readPayment($handlingSubject);

        /** @var Payment $orderPayment */
        $orderPayment = $paymentDO->getPayment();

        $orderPayment->setTransactionId($response['response'][Config::TXN_ID]);
        $orderPayment->setIsTransactionClosed(false);

        $additionalInfo = $orderPayment->getAdditionalInformation();
        $orderPayment->setTransactionAdditionalInfo(
            Transaction::RAW_DETAILS,
            $additionalInfo
        );
    }
}
