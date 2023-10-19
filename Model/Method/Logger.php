<?php

namespace Payoneer\OpenPaymentGateway\Model\Method;

use Magento\Payment\Gateway\ConfigInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Class Logger for payment related information (request, response, etc.) which is used for debug.
 *
 * @api
 * @since 100.0.2
 */
class Logger
{
    const MODULE_NAME = 'Payoneer_OpenPaymentGateway';
    const DEBUG_KEYS_MASK = '****';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ConfigInterface|null
     */
    private $config;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var ModuleListInterface
     */
    protected $moduleList;

    /**
     * @param ProductMetadataInterface $productMetadata
     * @param ModuleListInterface $moduleList
     * @param LoggerInterface $logger
     * @param ConfigInterface|null $config
     */
    public function __construct(
        ProductMetadataInterface $productMetadata,
        ModuleListInterface $moduleList,
        LoggerInterface $logger,
        ConfigInterface $config = null
    ) {
        $this->productMetadata = $productMetadata;
        $this->moduleList = $moduleList;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * Logs payment related information used for debug
     *
     * @param array <mixed> $data
     * @param array <mixed>|null $maskKeys
     * @param bool|null $forceDebug
     * @return void
     */
    public function debug(array $data, array $maskKeys = null, $forceDebug = null)
    {
        $data = array_merge($data, $this->getProductMetaData());
        $maskKeys = $maskKeys !== null ? $maskKeys : $this->getDebugReplaceFields();
        $debugOn = $forceDebug !== null ? $forceDebug : $this->isDebugOn();
        if ($debugOn === true) {
            $data = $this->filterDebugData(
                $data,
                $maskKeys
            );
            $this->logger->debug(var_export($data, true));
        }
    }

    /**
     * Returns configured keys to be replaced with mask
     *
     * @return array <mixed>
     */
    private function getDebugReplaceFields()
    {
        if ($this->config && $this->config->getValue('debugReplaceKeys')) {
            return explode(',', $this->config->getValue('debugReplaceKeys'));
        }
        return [];
    }

    /**
     * Whether debug is enabled in configuration
     *
     * @return bool
     */
    private function isDebugOn()
    {
        return $this->config && (bool)$this->config->getValue('debug');
    }

    /**
     * Recursive filter data by private conventions
     *
     * @param array <mixed> $debugData
     * @param array <mixed> $debugReplacePrivateDataKeys
     * @return array <mixed>
     */
    protected function filterDebugData(array $debugData, array $debugReplacePrivateDataKeys)
    {
        $debugReplacePrivateDataKeys = array_map('strtolower', $debugReplacePrivateDataKeys);

        foreach (array_keys($debugData) as $key) {
            if (in_array(strtolower($key), $debugReplacePrivateDataKeys)) {
                $debugData[$key] = self::DEBUG_KEYS_MASK;
            } elseif (is_array($debugData[$key])) {
                $debugData[$key] = $this->filterDebugData($debugData[$key], $debugReplacePrivateDataKeys);
            }
        }
        return $debugData;
    }

    /**
     * Get Magento application product metadata
     * @return array <mixed>
     */
    public function getProductMetaData()
    {
        return [
            'magentoVersion'    =>  $this->productMetadata->getVersion(),
            'magentoEdition'    =>  $this->productMetadata->getEdition(),
            'moduleVersion'     =>  $this->getModuleVersion(),
            'phpVersion'        =>  phpversion()
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
