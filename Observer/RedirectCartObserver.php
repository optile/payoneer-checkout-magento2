<?php

namespace Payoneer\OpenPaymentGateway\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Payoneer\OpenPaymentGateway\Model\Helper;

/**
 * Class RedirectCartObserver
 *
 * Update order status to payment_review in case of invalid transactions
 *
 */
class RedirectCartObserver implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * RedirectCartObserver constructor.
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param Helper $helper
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        Helper $helper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
    }

    /**
     * Change order status to payment_review and add transaction
     *
     * @param Observer $observer
     * @return Redirect|void
     */
    public function execute(Observer $observer)
    {
        if ($this->checkoutSession->getPayoneerInvalidTxn()) {
            /** @var $orderInstance Order */
            $order = $observer->getOrder();/** @phpstan-ignore-line */
            $this->setOrderStatus($order);

            $this->helper->unsetPayoneerInvalidTxnSession();
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    public function setOrderStatus($order)
    {
        $order->setState('canceled')->setStatus('canceled');
        $this->orderRepository->save($order);
    }
}
