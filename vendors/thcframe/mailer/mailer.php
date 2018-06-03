<?php

namespace THCFrame\Mailer;

use THCFrame\Core\Base;
use THCFrame\Mailer\Exception;
use THCFrame\Core\Core;
use THCFrame\Registry\Registry;

/**
 * Class for sending emails
 *
 * @author Tomy
 */
class Mailer extends Base
{

    /** @var Swift_MailTransport */
    private $transporter;

    /** @var Swift_Mailer */
    private $mailer;

    /** @var Swift_Message */
    private $message;

    /** @var THCFrame\Configuration\Configuration */
    private $config;

    /**
     * @readwrite
     * @var string
     */
    protected $subject;

    /**
     * @readwrite
     * @var string
     */
    protected $body;

    /**
     * @readwrite
     * @var string
     */
    protected $from;

    /**
     * @readwrite
     * @var array
     */
    protected $sendTo = [];

    /**
     *
     * @param type $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        try {
            require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
            $this->transporter = \Swift_MailTransport::newInstance();
            $this->mailer = \Swift_Mailer::newInstance($this->transporter);
            $this->message = \Swift_Message::newInstance(null);
            $this->config = Registry::get('configuration');
            
            $this->message->setEncoder(new \Swift_Mime_ContentEncoder_PlainContentEncoder('8bit'));            
        } catch (\Exception $e) {
            Core::getLogger()->error('Exception while initializing mailer: {exception}', ['exception' => $e]);
        }
    }

    public function getSubject()
    {
        if (ENV != 'live') {
            return '[TEST] ' . $this->subject;
        }
        return $this->subject;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getFrom()
    {
        return $this->from;
    }

    public function getSendTo()
    {
        return $this->sendTo;
    }

    /**
     *
     * @param type $glue
     * @return string
     */
    public function getSendToAsString($glue = ';')
    {
        if (!empty($this->sendTo)) {
            return implode($glue, $this->sendTo);
        } else {
            return '';
        }
    }

    /**
     *
     * @param type $subject
     * @return \THCFrame\Mailer\Mailer
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     *
     * @param type $body
     * @return \THCFrame\Mailer\Mailer
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     *
     * @param type $from
     * @return \THCFrame\Mailer\Mailer
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     *
     * @param type $sendTo
     * @return \THCFrame\Mailer\Mailer
     */
    public function setSendTo($sendTo)
    {
        if (is_array($sendTo)) {
            foreach ($sendTo as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->sendTo[] = $email;
                }
            }
        } else {
            if (filter_var($sendTo, FILTER_VALIDATE_EMAIL)) {
                $this->sendTo[] = $sendTo;
            }
        }
        return $this;
    }

    /**
     *
     * @param bool $oneByOne
     * @return boolean
     */
    public function send($oneByOne = false)
    {
        try {
            $this->message->setSubject($this->getSubject())
                    ->setBody($this->getBody(), 'text/html');

            if (null === $this->from || !filter_var($this->from, FILTER_VALIDATE_EMAIL)) {
                $this->message->setFrom($this->config->system->defaultemail);
            } else {
                $this->message->setFrom($this->from);
            }

            if (empty($this->sendTo)) {
                $this->setSendTo($this->config->system->adminemail)
                        ->setSendTo($this->config->system->defaultemail);
            }

            if ($oneByOne === true) {
                $statusSend = true;
                foreach ($this->getSendTo() as $recipient) {
                    $this->message->setTo([]);
                    $this->message->setTo($recipient);

                    if ($this->mailer->send($this->message)) {

                    } else {
                        $statusSend = false;
                        Core::getLogger()->error('Send email failed. Email: {message}', ['message' => serialize($this->message)]);
                    }
                }

                return $statusSend;
            } else {
                $this->message->setTo($this->getSendTo());

                if ($this->mailer->send($this->message)) {
                    return true;
                } else {
                    Core::getLogger()->error('Send email failed. Email: {message}', ['message' => serialize($this->message)]);
                    return false;
                }
            }
        } catch (Exception $ex) {
            Core::getLogger()->error('Send email failed. Exception: {exception}', ['exception' => serialize($ex)]);
            return false;
        }
    }

}
