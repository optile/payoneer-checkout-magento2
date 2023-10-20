<?php

namespace Payoneer\OpenPaymentGateway\Controller\Adminhtml\Downloadlogs;

use Magento\Framework\Controller\Result\Redirect;
use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Payoneer\OpenPaymentGateway\Model\Adminhtml\DownloadLogs\DownloadLogsBase;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Filesystem\DirectoryList;

/**
 *  Controller for downloading Payoneer logs
 */
class Download extends Action
{
    /**
     * @var DownloadLogsBase
     */
    private DownloadLogsBase $downloadLogBase;


    /**
     * @var DirectoryList
     */
    private DirectoryList $dirList;

    /**
     * @param DownloadLogsBase $downloadLogBase
     * @param Context $context
     * @param DirectoryList $dirList
     */
    public function __construct(
        DownloadLogsBase $downloadLogBase,
        Context          $context,
        DirectoryList $dirList
    ) {
        $this->downloadLogBase = $downloadLogBase;
        parent::__construct($context);
        $this->dirList = $dirList;
    }

    /**
     * Downloads the zipped log file
     *
     * @throws Exception
     */
    public function execute(): ResultInterface
    {
        try {
            $logFiles = array(
                '/payoneer.log',
                '/payoneer_notification.log',
                '/system.log',
                '/debug.log',
                '/exception.log'
            );
            $destination = $this->dirList->getPath('log').'/payoneer_log.zip';
            $zipFileLocation = $this->downloadLogBase->getZip($logFiles, $destination);
            if ($zipFileLocation) {
                $this->downloadLogBase->downloadFile($zipFileLocation);
            }
            return $this->resultFactory->create(ResultFactory::TYPE_RAW);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while downloading the log. ') .
                ' ' .
                $e->getMessage()
            );
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setRefererOrBaseUrl();
            return $resultRedirect;
        }
    }
}
