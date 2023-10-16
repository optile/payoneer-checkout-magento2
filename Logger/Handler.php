<?php

namespace Payoneer\OpenPaymentGateway\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base as BaseHandler;

/**
 * Logger handler class for Payoneer
 */
class Handler extends BaseHandler
{
    /**
     * @var string
     */
    protected $fileName;

    /**
     * Handler constructor.
     * @param DriverInterface $filesystem
     * @param string $fileName
     * @param null $filePath
     * @throws \Exception
     */
    public function __construct(
        DriverInterface $filesystem,
        $fileName,
        $filePath = null
    ) {
        $this->fileName = $fileName;
        parent::__construct($filesystem, $filePath);
    }

    /**
     * Gets logs file location
     *
     * @return string
     */
    public function getLogLocation(): string
    {
        return $this->fileName;
    }
}
