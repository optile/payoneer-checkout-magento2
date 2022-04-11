<?php

namespace Payoneer\OpenPaymentGateway\Logger;

use Payoneer\OpenPaymentGateway\Model\Helper;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Payoneer\OpenPaymentGateway\Gateway\Config\Config;

/**
 * NotificationLogger class
 * Log the notification related errors
 */
class NotificationLogger extends Logger
{
    /**
     * @var array <mixed>
     */
    private $generalInfo = [];

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var Config
     */
    private $config;

    /**
     * NotificationLogger construct
     *
     * @param Helper $helper
     * @param Config $config
     * @param string             $name       The logging channel
     * @param HandlerInterface[] $handlers   Optional stack of handlers
     * @param callable[]         $processors Optional array of processors
     * @return void
     */
    public function __construct(
        Helper $helper,
        Config $config,
        $name,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
        $this->helper = $helper;
        $this->config = $config;
    }

    /**
     * Adds a log record at the ERROR level.
     *
     * @param  string $message The log message
     * @param  array <mixed>  $context The log context
     * @return bool   Whether the record has been processed
     */
    public function addError($message, array $context = [])
    {
        if (!$this->config->isDebuggingEnabled()) {
            return true;
        }
        $this->prepareGeneralInfo();
        $message = $this->addGeneralInfoToMessage($message);
        return $this->addRecord(static::ERROR, $message, $context);
    }

    /**
     * Prepare the general info data for logging.
     *
     * @return void
     */
    private function prepareGeneralInfo()
    {
        if (empty($this->generalInfo)) {
            $this->generalInfo = $this->helper->getProductMetaData();
        }
    }

    /**
     * Append the general info data before the message.
     *
     * @param string $message
     * @return string
     */
    private function addGeneralInfoToMessage($message)
    {
        $finalMessage = '[';
        foreach ($this->generalInfo as $key => $value) {
            $finalMessage .= $key . '=' . $value . '|';
        }
        $finalMessage .= ']' . $message;

        return $finalMessage;
    }
}
