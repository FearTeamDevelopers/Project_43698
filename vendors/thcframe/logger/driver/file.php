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
        $options = array(
            'path' => 'application' . DIRECTORY_SEPARATOR . 'logs',
        );

        parent::__construct($options);

        $this->path = APP_PATH . DIRECTORY_SEPARATOR . trim($this->path, DIRECTORY_SEPARATOR);

        if (!is_dir($this->path)) {
            @mkdir($this->path, self::DIR_CHMOD, true);
        }

        $this->deleteOldLogs();
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
        if (!file_exists($file)) {
            file_put_contents($file, $message, FILE_APPEND);
        } elseif (file_exists($file) && filesize($file) < self::MAX_FILE_SIZE) {
            file_put_contents($file, $message, FILE_APPEND);
        } elseif (file_exists($file) && filesize($file) > self::MAX_FILE_SIZE) {
            for ($i = 1; $i < 100; $i+=1) {
                if (!file_exists($file . $i)) {
                    file_put_contents($file . $i, $message, FILE_APPEND);
                } elseif (file_exists($file . $i) && filesize($file . $i) < self::MAX_FILE_SIZE) {
                    file_put_contents($file . $i, $message, FILE_APPEND);
                } else {
                    continue;
                }
            }
        }
    }

    private function appendLine($level, $message, $context)
    {
        $message = '[' . date('Y-m-d H:i:s') . '][' . $level . ']' . $this->interpolate($message, $context) . PHP_EOL;

        $path = $this->prepareLogPath($level);
        $this->putContents($path, $message);
    }

    /**
     * Delete old log files
     *
     * @param string $olderThan   date yyyy-mm-dd
     */
    private function deleteOldLogs($olderThan = null)
    {
        if (!is_dir($this->path)) {
            return;
        }

        if(null === $olderThan){
            $olderThan = strtotime('-90 days');
        }

        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->path), \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($objects as $path => $item) {
            if ($item->isFile()) {
                if (time() - $item->getMTime() > time() - $olderThan) {
                    @unlink($path);
                }
            }
        }
    }

    public function emergency($message, array $context = array())
    {
        $this->appendLine(self::EMERGENCY, $message, $context);
        return $this;
    }

    public function alert($message, array $context = array())
    {
        $this->appendLine(self::ALERT, $message, $context);
        return $this;
    }

    public function critical($message, array $context = array())
    {
        $this->appendLine(self::CRITICAL, $message, $context);
        return $this;
    }

    public function error($message, array $context = array())
    {
        $this->appendLine(self::ERROR, $message, $context);
        return $this;
    }

    public function warning($message, array $context = array())
    {
        $this->appendLine(self::WARNING, $message, $context);
        return $this;
    }

    public function notice($message, array $context = array())
    {
        $this->appendLine(self::NOTICE, $message, $context);
        return $this;
    }

    public function info($message, array $context = array())
    {
        $this->appendLine(self::INFO, $message, $context);
        return $this;
    }

    public function debug($message, array $context = array())
    {
        $this->appendLine(self::DEBUG, $message, $context);
        return $this;
    }

    public function log($level, $message, array $context = array())
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
                $this->error($message, $context);
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
