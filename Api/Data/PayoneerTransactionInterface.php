<?php
declare(strict_types=1);

namespace Payoneer\OpenPaymentGateway\Api\Data;

interface PayoneerTransactionInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    const TRANSACTION_ID    =   'transaction_id';
    const CUSTOMER_ID       =   'customer_id';
    const REGISTRATION_ID   =   'registration_id';

    /**
     * Get TransactionId.
     *
     * @return int
     */
    public function getTransactionId();

    /**
     * Set TransactionId.
     * @param int $transactionId
     * @return void
     */
    public function setTransactionId($transactionId);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * Set CustomerId.
     * @param int $customerId.
     * @return void
     */
    public function setCustomerId($customerId);

    /**
     * Get RegistrationId.
     *
     * @return string
     */
    public function getRegistrationId();

    /**
     * Set RegistrationId.
     * @param string $registrationId.
     * @return void
     */
    public function setRegistrationId($registrationId);
}
