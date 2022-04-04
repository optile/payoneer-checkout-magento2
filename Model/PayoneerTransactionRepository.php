<?php
declare(strict_types=1);

namespace Payoneer\OpenPaymentGateway\Model;

use Payoneer\OpenPaymentGateway\Api\PayoneerTransactionRepositoryInterface;
use Payoneer\OpenPaymentGateway\Api\Data\PayoneerTransactionInterface;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerTransaction as ResourceModel;
use Magento\Framework\Exception\CouldNotSaveException;
use Payoneer\OpenPaymentGateway\Model\ResourceModel\PayoneerTransaction\CollectionFactory;

/**
 * Class PayoneerTransactionRepository
 * Repository class for Payoneer transaction
 */
class PayoneerTransactionRepository implements PayoneerTransactionRepositoryInterface
{
    /**
     * @var ResourceModel
     */
    protected $resource;

    /**
     * @var PayoneerTransactionFactory
     */
    protected $modelFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * PayoneerTransactionRepository constructor.
     * @param ResourceModel $resource
     * @param PayoneerTransactionFactory $modelFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        ResourceModel $resource,
        PayoneerTransactionFactory $modelFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->modelFactory = $modelFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param PayoneerTransactionInterface $payoneerTransaction
     * @return mixed|PayoneerTransactionInterface
     * @throws CouldNotSaveException
     */
    public function save(PayoneerTransactionInterface $payoneerTransaction)
    {
        try {
            /** @var PayoneerTransaction $payoneerTransaction */
            $this->resource->save($payoneerTransaction);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $payoneerTransaction;
    }

    /**
     * @param int $customerId
     * @return PayoneerTransaction|null
     */
    public function getByCustomerId($customerId)
    {
        $transactions = $this->collectionFactory->create();
        $transactions->addFieldToFilter(PayoneerTransactionInterface::CUSTOMER_ID, (string)$customerId);
        if ($transactions->count()>0) {
            /** @var PayoneerTransaction $transaction */
            $transaction = $transactions->getFirstItem();
            return $transaction;
        }
        return null;
    }

    /**
     * @return mixed|PayoneerTransaction
     */
    public function create()
    {
        return $this->modelFactory->create();
    }
}
