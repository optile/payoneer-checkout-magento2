<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
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
    private $assetRepository;

    /**
     * Helper constructor.
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param AssetRepository $assetRepository
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        AssetRepository $assetRepository
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->assetRepository = $assetRepository;
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
     * @param array $params
     * @return string
     */
    public function getStaticFilePath($fileId, $params)
    {
        return $this->assetRepository->getUrlWithParams($fileId, $params);
    }
}
