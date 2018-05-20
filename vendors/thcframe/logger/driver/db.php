<?php

namespace THCFrame\Logger\Driver;

use THCFrame\Logger;
use THCFrame\Logger\Model\LoggerModel;

/**
 * Db logger class
 */
class Db extends Logger\Driver
{

    /**
     * Object constructor
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
    }

    private function appendLine($level, $message, $context)
    {
        $model = new LoggerModel([
            'level' => $level,
            'identifier' => $message,
            'body' => json_encode($context),
        ]);

        if ($model->validate()) {
            $model->save();
        }
    }

    public function emergency($message, array $context = [])
    {
        $this->appendLine(self::EMERGENCY, $message, $context);
        return $this;
    }

    public function alert($message, array $context = [])
    {
        $this->appendLine(self::ALERT, $message, $context);
        return $this;
    }

    public function critical($message, array $context = [])
    {
        $this->appendLine(self::CRITICAL, $message, $context);
        return $this;
    }

    public function error($message, array $context = [])
    {
        $this->appendLine(self::ERROR, $message, $context);
        return $this;
    }

    public function warning($message, array $context = [])
    {
        $this->appendLine(self::WARNING, $message, $context);
        return $this;
    }

    public function notice($message, array $context = [])
    {
        $this->appendLine(self::NOTICE, $message, $context);
        return $this;
    }

    public function info($message, array $context = [])
    {
        $this->appendLine(self::INFO, $message, $context);
        return $this;
    }

    public function debug($message, array $context = [])
    {
        $this->appendLine(self::DEBUG, $message, $context);
        return $this;
    }

    public function log($level, $message, array $context = [])
    {
        switch ($level) {
            case self::EMERGENCY:
                $this->emergency($message, $context);
                break;
            case self::ALERT:
                $this->alert($message, $context);
                break;
            case self::CRITICAL:
                $this->critical($message, $context);
                break;
            case self::ERROR:
                $this->error($message, $context);
                break;
            case self::WARNING:
                $this->warning($message, $context);
                break;
            case self::NOTICE:
                $this->notice($message, $context);
                break;
            case self::INFO:
                $this->info($message, $context);
                break;
            case self::DEBUG:
                $this->debug($message, $context);
                break;
        }

        return $this;
    }

}
