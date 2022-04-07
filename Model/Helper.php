<?php
namespace Payoneer\OpenPaymentGateway\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Class Helper
 *
 * Module helper file
 */
class Helper
{
    const MODULE_NAME = 'Payoneer_OpenPaymentGateway';

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
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * Helper constructor.
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param AssetRepository $assetRepository
     * @param ResourceConnection $resourceConnection
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     * @return void
     */
    public function __construct(
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        AssetRepository $assetRepository,
        ResourceConnection $resourceConnection,
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->assetRepository = $assetRepository;
        $this->resourceConnection = $resourceConnection;
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
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
     */
    public function saveRegistrationId($registrationId, $customerId)
    {
        $regId = $this->getRegistrationId($customerId);
        if (!$regId) {
            $data = [
                    'customer_id' => $customerId,
                    'registration_id' => $registrationId
                    ];
            $connection = $this->resourceConnection->getConnection();
            $connection->insertOnDuplicate(
                $this->resourceConnection->getTableName('payoneer_payment_transaction'),
                $data
            );
        }
    }

    /**
     * Get customer registration id
     * @param int $customerId
     * @return string
     */
    public function getRegistrationId($customerId)
    {
        $connection = $this->resourceConnection->getConnection();
        $query = $connection->select()->from(
            ['e' => $this->resourceConnection->getTableName('payoneer_payment_transaction')],
            'e.registration_id'
        )->where(
            $connection->quoteIdentifier('e.customer_id') . ' = ?',
            $customerId
        );
        return $connection->fetchOne($query);
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

    /**
     * Get Magento application product metadata
     *
     * @return array <mixed>
     */
    public function getProductMetaData()
    {
        return [
            'magentoVersion'    =>  $this->productMetadata->getVersion(),
            'magentoEdition'    =>  $this->productMetadata->getEdition(),
            'moduleVersion'     =>  $this->getModuleVersion()
        ];
    }

    /**
     * Find the installed module version
     *
     * @return mixed
     */
    public function getModuleVersion()
    {
        $module = $this->moduleList->getOne(self::MODULE_NAME);
        return $module ? $module ['setup_version'] : null;
    }
}
