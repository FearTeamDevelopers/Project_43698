<?php

namespace THCFrame\Logger\Driver;

use THCFrame\Logger;
use THCFrame\Registry\Registry;
use THCFrame\Mailer\Mailer;

/**
 * Email logger class
 */
class Email extends Logger\Driver
{

    private $emailBody = null;
    private $appName;
    private $sendTo;
    private $level;

    public function __construct($options = null)
    {
        parent::__construct($options);

        $config = Registry::get('configuration');
        $this->sendTo = $config->system->adminemail;
        $this->appName = $config->system->appname;

        if ($this->emailBody === null) {
            $this->emailBody = file_get_contents('./vendors/thcframe/logger/template/emailbody.tpl');
        }
    }

    private function sendEmail($message)
    {
        $mailer = new Mailer();
        $mailer->setSubject($this->appName . ' ' . strtoupper($this->level))
                ->setBody($message)
                ->setFrom('info@' . strtolower($this->appName) . '.cz')
                ->setSendTo($this->sendTo);

        file_put_contents(APP_PATH . '/application/logs/' . date('Y-m-d') . '_mail.log', $message, FILE_APPEND);

        $mailer->send();
    }

    private function prepareEmailBody($message, array $context = [])
    {
        $defaultContext = ['date' => '[' . date('Y-m-d H:i:s') . ']',
            'level' => $this->level,
            'appname' => $this->appName,
            'message' => $this->interpolate($message, $context),
        ];

        return $this->interpolate($this->emailBody, $defaultContext);
    }

    /**
     *
     * @param type $message
     */
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
            default :
                $this->info($message, $context);
                break;
        }

        return $this;
    }

    public function alert($message, array $context = [])
    {
        $this->level = self::ALERT;
        $body = $this->prepareEmailBody($message, $context);
        $this->sendEmail($body);

        return $this;
    }

    public function critical($message, array $context = [])
    {
        $this->level = self::CRITICAL;
        $body = $this->prepareEmailBody($message, $context);
        $this->sendEmail($body);

        return $this;
    }

    public function debug($message, array $context = [])
    {
        return $this;
    }

    public function emergency($message, array $context = [])
    {
        $this->level = self::EMERGENCY;
        $body = $this->prepareEmailBody($message, $context);
        $this->sendEmail($body);

        return $this;
    }

    public function error($message, array $context = [])
    {
        $this->level = self::ERROR;
        $body = $this->prepareEmailBody($message, $context);
        $this->sendEmail($body);

        return $this;
    }

    public function info($message, array $context = [])
    {
        return $this;
    }

    public function notice($message, array $context = [])
    {
        return $this;
    }

    public function warning($message, array $context = [])
    {
        return $this;
    }

}
