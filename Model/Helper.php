<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;

/**
 * Class Helper
 *
 * Module helper file
 */
class Helper
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var PayoneerTransactionRepository
     */
    protected $payoneerTransactionRepository;

    /**
     * Helper constructor.
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param AssetRepository $assetRepository
     * @param ResourceConnection $resourceConnection
     * @param PayoneerTransactionRepository $payoneerTransactionRepository
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        AssetRepository $assetRepository,
        ResourceConnection $resourceConnection,
        PayoneerTransactionRepository $payoneerTransactionRepository
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->assetRepository = $assetRepository;
        $this->resourceConnection = $resourceConnection;
        $this->payoneerTransactionRepository = $payoneerTransactionRepository;
    }

    /**
     * Redirects to cart
     *
     * @param string $message
     * @return Redirect
     */
    public function redirectToCart($message)
    {
        $this->messageManager->addErrorMessage($message);
        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }

    /**
     * Get the static file's path
     * @param string $fileId
     * @param array <mixed> $params
     * @return string
     */
    public function getStaticFilePath($fileId, $params)
    {
        return $this->assetRepository->getUrlWithParams($fileId, $params);
    }

    /**
     * Save customer registration id
     * @param string $registrationId
     * @param int $customerId
     * @return void
     * @throws CouldNotSaveException
     */
    public function saveRegistrationId($registrationId, $customerId)
    {
        $regId = $this->getRegistrationId($customerId);
        if (!$regId) {
            $payoneerTransactionModel = $this->payoneerTransactionRepository->create();
            $payoneerTransactionModel->setCustomerId($customerId);
            $payoneerTransactionModel->setRegistrationId($registrationId);
            $this->payoneerTransactionRepository->save($payoneerTransactionModel);
        }
    }

    /**
     * Get customer registration id
     * @param int $customerId
     * @return string | null
     */
    public function getRegistrationId($customerId)
    {
        $payoneerTransaction = $this->payoneerTransactionRepository->getByCustomerId($customerId);
        return $payoneerTransaction ? $payoneerTransaction->getRegistrationId() : null;
    }

    /**
     * Format amount to 2 decimal points
     * @param float|null $amount
     * @return string|null
     */
    public function formatNumber($amount)
    {
        return $amount ? number_format($amount, 2, '.', '') : null;
    }
}
