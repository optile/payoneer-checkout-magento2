<?php

namespace Payoneer\OpenPaymentGateway\Controller\Redirect;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Payoneer\OpenPaymentGateway\Model\PayoneerNotificationFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\Request\Http;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;
use Payoneer\OpenPaymentGateway\Api\PayoneerNotificationRepositoryInterface;
use Payoneer\OpenPaymentGateway\Api\Data\NotificationInterfaceFactory;
use Payoneer\OpenPaymentGateway\Model\PayoneerNotification;
use Exception;

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
     * Notification constructor.
     *
     * @param PayoneerNotificationFactory $payoneerNotification
     * @param Http $request
     * @return void
     */
    public function __construct(
        PayoneerNotificationFactory $payoneerNotification,
        PayoneerNotificationRepositoryInterface $notificationRepository,
        NotificationInterfaceFactory $notificationFactory,
        Http $request
    ) {
        $this->payoneerNotification = $payoneerNotification;
        $this->notificationRepository = $notificationRepository;
        $this->notificationFactory = $notificationFactory;
        $this->request = $request;
    }

    /**
     * Listen notification from payoneer side and save to db.
     *
     * @return ResponseInterface|ResultInterface|void|null
     * @throws \Exception
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
            // log error
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
        return true;
    }
}
