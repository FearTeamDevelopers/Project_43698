<?php

namespace THCFrame\Logger\Driver;

use THCFrame\Logger;
use THCFrame\Registry\Registry;

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
        require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
        $transport = \Swift_MailTransport::newInstance();
        $mailer = \Swift_Mailer::newInstance($transport);

        $email = \Swift_Message::newInstance(null)
                ->setSubject($this->appName . ' ' . strtoupper($this->level))
                ->setFrom('info@' . strtolower($this->appName) . '.cz')
                ->setTo($this->sendTo)
                ->setBody($message);

        file_put_contents($this->path . date('Y-m-d') . '_mail.log', $email->toString());

        $result = $mailer->send($email);
    }

    private function prepareEmailBody($message, array $context = array())
    {
        $defaultContext = array('date' => '[' . date('Y-m-d H:i:s') . ']',
            'level' => $this->level,
            'appname' => $this->appName,
            'message' => $this->interpolate($message, $context),
        );

        return $this->interpolate($this->emailBody, $defaultContext);
    }

    /**
     *
     * @param type $message
     */
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

    public function alert($message, array $context = array())
    {
        $this->level = self::ALERT;
        $body = $this->prepareEmailBody($message, $context);
        $this->sendEmail($body);

        return $this;
    }

    public function critical($message, array $context = array())
    {
        $this->level = self::CRITICAL;
        $body = $this->prepareEmailBody($message, $context);
        $this->sendEmail($body);

        return $this;
    }

    public function debug($message, array $context = array())
    {
        return $this;
    }

    public function emergency($message, array $context = array())
    {
        $this->level = self::EMERGENCY;
        $body = $this->prepareEmailBody($message, $context);
        $this->sendEmail($body);

        return $this;
    }

    public function error($message, array $context = array())
    {
        $this->level = self::ERROR;
        $body = $this->prepareEmailBody($message, $context);
        $this->sendEmail($body);

        return $this;
    }

    public function info($message, array $context = array())
    {
        return $this;
    }

    public function notice($message, array $context = array())
    {
        return $this;
    }

    public function warning($message, array $context = array())
    {
        return $this;
    }

}
