<?php

namespace THCFrame\Logger;

use THCFrame\Logger\LoggerInterface;

/**
 *
 */
class LoggerComposite implements LoggerInterface
{

    private $childs = [];

    public function __construct()
    {

    }

    public function addChild(LoggerInterface $logger, $key = null)
    {
        if ($key === null) {
            $key = count($this->childs) + 1;
        }
        $this->childs[$key] = $logger;

        return $this;
    }

    public function getChild($key)
    {
        if (isset($this->childs[$key])) {
            return $this->childs[$key];
        } else {
            return null;
        }
    }

    public function alert($message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->alert($message, $context);
            }
        }

        return $this;
    }

    public function critical($message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->critical($message, $context);
            }
        }

        return $this;
    }

    public function debug($message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->debug($message, $context);
            }
        }

        return $this;
    }

    public function emergency($message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->emergency($message, $context);
            }
        }

        return $this;
    }

    public function error($message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->error($message, $context);
            }
        }

        return $this;
    }

    public function info($message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->info($message, $context);
            }
        }

        return $this;
    }

    public function log($level, $message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->log($level, $message, $context);
            }
        }

        return $this;
    }

    public function notice($message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->notice($message, $context);
            }
        }

        return $this;
    }

    public function warning($message, array $context = [])
    {
        if (!empty($this->childs)) {
            foreach ($this->childs as $logger) {
                $logger->warning($message, $context);
            }
        }

        return $this;
    }

}
