<?php

namespace Payoneer\OpenPaymentGateway\Model\Adminhtml\DownloadLogs;

use Dk4software\Debug;
use Exception;
use \Magento\Framework\Filesystem\Io\File;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class for handling zipping and downloading log file
 */
class DownloadLogsBase
{
    /**
     * @var FileFactory
     */
    private FileFactory $fileFactory;

    /**
     * @var File
     */
    private File $file;

    /**
     * @var DirectoryList
     */
    private DirectoryList $dirList;

    /**
     * @param File $file
     * @param FileFactory $fileFactory
     */
    public function __construct(
        File $file,
        FileFactory $fileFactory,
        DirectoryList $dirList
    ) {
        $this->file = $file;
        $this->fileFactory = $fileFactory;
        $this->dirList = $dirList;
    }

    /**
     * Download corresponding log file as a zip file
     *
     * @param string $location
     * @return ResponseInterface
     * @throws LocalizedException
     */
    public function downloadFile(string $location): ResponseInterface
    {
        try {
            $content = [];
            $downloadedFileName = 'logfile.zip';
            $content['type'] = 'filename';
            $content['value'] = $location;
            $content['rm'] = 1;
            return $this->fileFactory->create($downloadedFileName, $content, DirectoryList::VAR_DIR);
        } catch (Exception $e) {
            $customExceptionMessage = 'Error while downloading file: ' . $e->getMessage();
                throw new LocalizedException(__($customExceptionMessage));
        }
    }

    /**
     * Create zip file
     *
     * @param array $logFiles
     * @param string $destination
     * @return string
     */
    public function getZip(array $logFiles, string $destination): string
    {
        $fileAdded = false;
        $logDir = $this->dirList->getPath('log');
        $zip = new \ZipArchive();
        $zip->open($destination, \ZipArchive::CREATE);
        foreach($logFiles as $logFile) {
            if(!file_exists($logDir . $logFile)) continue;
            $filename = $this->file->getPathInfo($logFile);
            $zip->addFile($logDir .$logFile, $filename['basename']);/* @phpstan-ignore-line */
            $fileAdded = true;
        }
        $zip->close();
        if(!$fileAdded) throw new LocalizedException(__('No log files found!'));
        return $destination;
    }
}
