<?php

namespace Payoneer\OpenPaymentGateway\Logger;

use Payoneer\OpenPaymentGateway\Model\Helper;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

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
     * NotificationLogger construct
     *
     * @param Helper $helper
     * @param string             $name       The logging channel
     * @param HandlerInterface[] $handlers   Optional stack of handlers
     * @param callable[]         $processors Optional array of processors
     * @return void
     */
    public function __construct(
        Helper $helper,
        $name,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);
        $this->helper = $helper;
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
