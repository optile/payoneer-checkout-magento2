<?php

namespace Payoneer\OpenPaymentGateway\Model\Creditmemo;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Magento\Sales\Api\InvoiceRepositoryInterface;

class CreditmemoCreator
{
    /**
     * @var CreditmemoFactory
     */
    protected $creditmemoFactory;

    /**
     * @var CreditmemoService
     */
    protected $creditmemoService;

    /**
     * @var InvoiceRepositoryInterface
     */
    protected $invoiceRepository;

    /**
     * CreditmemoCreator constructor
     *
     * @param CreditmemoFactory $creditmemoFactory
     * @param CreditmemoService $creditmemoService
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @return void
     */
    public function __construct(
        CreditmemoFactory $creditmemoFactory,
        CreditmemoService $creditmemoService,
        InvoiceRepositoryInterface $invoiceRepository
    ) {
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoService = $creditmemoService;
        $this->invoiceRepository = $invoiceRepository;
    }

    /**
     * Create creditmemo for full order.
     *
     * @param Order $order
     * @return bool
     * @throws LocalizedException
     */
    public function create($order)
    {
        try {
            $invoices = $order->getInvoiceCollection();
            $invoiceId = 0;
            foreach ($invoices as $invoice) {
                $invoiceId = $invoice->getId();
            }

            $invoice = $this->invoiceRepository->get($invoiceId);
            $creditmemo = $this->creditmemoFactory->createByOrder($order);
            $creditmemo->setData('invoice', $invoice);

            $this->creditmemoService->refund($creditmemo);
            return true;
        } catch (\Exception $e) {
            throw new LocalizedException(
                __('Failed to create the credit memo for the order %1', $order->getIncrementId())
            );
        }
    }
}
