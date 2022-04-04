<?php
declare(strict_types=1);

namespace Payoneer\OpenPaymentGateway\Api;

use Payoneer\OpenPaymentGateway\Api\Data\PayoneerTransactionInterface;

/**
 * Grid CRUD interface.
 * @api
 */
interface PayoneerTransactionRepositoryInterface
{

    /**
     * @param PayoneerTransactionInterface $payoneerTransaction
     * @return mixed
     */
    public function save(PayoneerTransactionInterface $payoneerTransaction);

    /**
     * @param int $customerId
     * @return mixed
     */
    public function getByCustomerId($customerId);

    /**
     * @return mixed
     */
    public function create();
}
