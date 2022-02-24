<?php
namespace Payoneer\OpenPaymentGateway\Gateway\Response;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;

class CaptureHandler implements HandlerInterface
{
    /**
    * Handles transaction id
    *
    * @param array $handlingSubject
    * @param array $response
    * @return void
    */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = SubjectReader::readPayment($handlingSubject);

        $payment = $paymentDO->getPayment();
        $order =$paymentDO->getOrder();

        $payment->setTransactionId($order->getOrderIncrementId());
        $payment->setIsTransactionClosed(false);
    }
}
