<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Exception;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Payoneer\OpenPaymentGateway\Api\Data\NotificationInterfaceFactory;
use Payoneer\OpenPaymentGateway\Api\PayoneerNotificationRepositoryInterface;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Model\PayoneerNotification;
use Payoneer\OpenPaymentGateway\Model\PayoneerNotificationFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Payoneer\OpenPaymentGateway\Logger\NotificationLogger;

/**
 * Class Notification
 *
 * Process Notification request
 */
class Notification implements CsrfAwareActionInterface
{
    /**
     * @var PayoneerNotificationFactory
     */
    protected $payoneerNotification;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var PayoneerNotificationRepositoryInterface
     */
    protected $notificationRepository;

    /**
     * @var NotificationInterfaceFactory
     */
    protected $notificationFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var NotificationLogger
     */
    protected $notificationLogger;

    /**
     * Notification constructor.
     *
     * @param PayoneerNotificationFactory $payoneerNotification
     * @param PayoneerNotificationRepositoryInterface $notificationRepository
     * @param NotificationInterfaceFactory $notificationFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param NotificationLogger $notificationLogger
     * @param Http $request
     */
    public function __construct(
        PayoneerNotificationFactory $payoneerNotification,
        PayoneerNotificationRepositoryInterface $notificationRepository,
        NotificationInterfaceFactory $notificationFactory,
        OrderCollectionFactory $orderCollectionFactory,
        NotificationLogger $notificationLogger,
        Http $request
    ) {
        $this->payoneerNotification = $payoneerNotification;
        $this->notificationRepository = $notificationRepository;
        $this->notificationFactory = $notificationFactory;
        $this->request = $request;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->notificationLogger = $notificationLogger;
    }

    /**
     * Listen notification from payoneer side and save to db.
     *
     * @return ResponseInterface|ResultInterface|void|null
     * @throws Exception
     */
    public function execute()
    {
        try {
            $urlParams = $this->request->getParams();
            $post = $this->request->getContent();
            $postArray = \Safe\json_decode($post, true);
            if ($postArray['entity'] == Config::ENTITY_PAYMENT) {
                /** @var  PayoneerNotification $notification */
                $notification = $this->notificationFactory->create();
                $notification->addData([
                    'transactionId' => $postArray['transactionId'],
                    'longId' => $postArray['longId'],
                    'content' => $post,
                    'order_id' => $urlParams['order_id'],
                    'cron_status' => 0
                ]);
                $this->notificationRepository->save($notification);
            }
        } catch (Exception $e) {
            $this->notificationLogger->addError(
                $e->getMessage()
            );
        }
        exit;// @codingStandardsIgnoreLine
    }

    /**
     * Get the token from the order payment additional info.
     *
     * @param string $orderId
     * @return string|null
     */
    private function getTokenFromOrder($orderId)
    {
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('increment_id', ['eq' => $orderId]);
        if ($collection->getSize()) {
            $order = $collection->getFirstItem();
            $payment = $order->getPayment();
            return $payment->getAdditionalInformation(Config::TOKEN_NOTIFICATION);
        }
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        $orderId = $request->getParam('order_id');
        $notificationToken = $request->getParam('token');
        $orderToken = $this->getTokenFromOrder($orderId);
        if ($notificationToken == '' || $notificationToken == null || $notificationToken != $orderToken) {
            $this->notificationLogger->addError(
                __(
                    'Invalid token for order #%1, order token = %2, received token = %3',
                    $orderId,
                    $orderToken,
                    $notificationToken
                )
            );
            return false;
        }
        return true;
    }
}
