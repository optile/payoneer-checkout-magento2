<?php

namespace Payoneer\OpenPaymentGateway\Api\Data;

/**
 * @api
 * @since 100.0.2
 */
interface NotificationInterface
{
    const TABLE = 'payoneer_notification';

    const ID = 'id';

    const TRANSACTION_ID = 'transactionId';

    const LONG_ID = 'longId';

    const ORDER_ID = 'order_id';

    const CONTENT = 'content';

    const CRON_STATUS = 'cron_status';

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    /**
     * Get notification id
     *
     * @return int
     */
    public function getId();

    /**
     * Set notification id
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);
    
    /**
     * Get transaction id
     *
     * @return string
     */
    public function getTransactionId();

    /**
     * Set transaction id
     *
     * @param string $txnId
     * @return $this
     */
    public function setTransactionId($txnId);

    /**
     * Get long id
     *
     * @return string
     */
    public function getLongId();

    /**
     * Set long id
     *
     * @param string $longId
     * @return $this
     */
    public function setLongId($longId);

    /**
     * Get order id
     *
     * @return string
     */
    public function getOrderId();

    /**
     * Set order id
     *
     * @param string $orderId
     * @return $this
     */
    public function setOrderId($orderId);

    /**
     * Get response content
     *
     * @return string
     */
    public function getContent();

    /**
     * Set response content
     *
     * @param string $response
     * @return $this
     */
    public function setContent($response);

    /**
     * Get response processed status
     *
     * @return bool
     */
    public function getCronStatus();

    /**
     * Set response processed status
     *
     * @param bool $status
     * @return $this
     */
    public function setCronStatus($status);
}
