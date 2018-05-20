<?php

namespace THCFrame\Logger\Driver;

use THCFrame\Logger;

/**
 * File logger class
 */
class File extends Logger\Driver
{

    /** Default options */
    const DIR_CHMOD = 0755;
    const FILE_CHMOD = 0644;
    const MAX_FILE_SIZE = 5000000;

    /**
     * Object constructor
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->path = APP_PATH . DIRECTORY_SEPARATOR . trim($this->path, DIRECTORY_SEPARATOR);

        if (!is_dir($this->path)) {
            @mkdir($this->path, self::DIR_CHMOD, true);
        }
    }

    private function prepareLogPath($level = self::INFO)
    {
        $pathTypeSuffix = date('Y') . DIRECTORY_SEPARATOR . date('m') . DIRECTORY_SEPARATOR . date('d');
        $path = $this->path . DIRECTORY_SEPARATOR . $level . DIRECTORY_SEPARATOR . $pathTypeSuffix;

        if (!is_dir($path)) {
            @mkdir($path, self::DIR_CHMOD, true);
        }

        return $path . DIRECTORY_SEPARATOR . 'app.log';
    }

    private function putContents($file, $message)
    {
        if (file_exists($file)) {
            $fileSize = filesize($file);

            if ($fileSize < self::MAX_FILE_SIZE) {
                file_put_contents($file, $message, FILE_APPEND);
            } elseif ($fileSize > self::MAX_FILE_SIZE) {
                for ($i = 1; $i < 100; $i += 1) {
                    if (!file_exists($file . $i)) {
                        file_put_contents($file . $i, $message, FILE_APPEND);
                    } elseif (filesize($file . $i) < self::MAX_FILE_SIZE) {
                        file_put_contents($file . $i, $message, FILE_APPEND);
                    } else {
                        continue;
                    }
                }
            }
        } else {
            file_put_contents($file, $message, FILE_APPEND);
        }
    }

    private function appendLine($level, $message, $context)
    {
        $message = '[' . date('Y-m-d H:i:s') . '][' . $level . ']' . $this->interpolate($message, $context) . PHP_EOL;

        $path = $this->prepareLogPath($level);
        $this->putContents($path, $message);
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
