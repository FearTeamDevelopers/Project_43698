<?php

namespace THCFrame\Mailer;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Mailer\Exception;
use THCFrame\Core\Core;
use THCFrame\Registry\Registry;

/**
 * Description of mailer
 *
 * @author Tomy
 */
class Mailer extends Base
{

    private $transporter;
    private $mailer;
    private $message;
    private $config;

    /**
     * @readwrite
     * @var type
     */
    protected $_subject;

    /**
     * @readwrite
     * @var type
     */
    protected $_body;

    /**
     * @readwrite
     * @var type
     */
    protected $_from;

    /**
     * @readwrite
     * @var type
     */
    protected $_sendTo = array();

    /**
     *
     * @param type $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        try {
            require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
            $this->transporter = \Swift_MailTransport::newInstance();
            $this->mailer = \Swift_Mailer::newInstance($this->transporter);
            $this->message = \Swift_Message::newInstance(null);
            $this->config = Registry::get('configuration');
        } catch (\Exception $e) {

        }
    }

    public function getSubject()
    {
        if (ENV != 'live') {
            return '[TEST] ' . $this->_subject;
        }
        return $this->_subject;
    }

    public function getBody()
    {
        return $this->_body;
    }

    public function getFrom()
    {
        return $this->_from;
    }

    public function getSendTo()
    {
        return $this->_sendTo;
    }

    public function getSendToAsString($glue = ';')
    {
        if (!empty($this->_sendTo)) {
            return implode($glue, $this->_sendTo);
        } else {
            return '';
        }
    }

    public function setSubject($subject)
    {
        $this->_subject = $subject;
        return $this;
    }

    public function setBody($body)
    {
        $this->_body = $body;
        return $this;
    }

    public function setFrom($from)
    {
        $this->_from = $from;
        return $this;
    }

    public function setSendTo($sendTo)
    {
        if (is_array($sendTo)) {
            foreach ($sendTo as $email) {
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->_sendTo[] = $email;
                }
            }
        } else {
            if (filter_var($sendTo, FILTER_VALIDATE_EMAIL)) {
                $this->_sendTo[] = $sendTo;
            }
        }
        return $this;
    }

    /**
     *
     * @param type $oneByOne
     * @param type $sendFrom
     * @return boolean
     */
    public function send($oneByOne = false)
    {
        try {
            $this->message->setSubject($this->getSubject())
                    ->setBody($this->getBody(), 'text/html');

            if (null === $sendFrom || !filter_var($sendFrom,
                            FILTER_VALIDATE_EMAIL)) {
                $this->message->setFrom($this->config->system->defaultemail);
            } else {
                $this->message->setFrom($sendFrom);
            }

            if (empty($this->_sendTo)) {
                $this->setSendTo($this->config->system->adminemail)
                        ->setSendTo($this->config->system->defaultemail);
            }

            if ($oneByOne === true) {
                $statusSend = true;
                foreach ($this->getSendTo() as $recipient) {
                    $this->message->setTo(array());
                    $this->message->setTo($recipient);

                    if ($this->mailer->send($this->message)) {

                    } else {
                        $statusSend = false;
                        Core::getLogger()->error('Send email failed. Email: {message}',
                                array('message' => serialize($this->message)));
                    }
                }

                return $statusSend;
            } else {
                $this->message->setTo($this->getSendTo());

                if ($this->mailer->send($this->message)) {
                    return true;
                } else {
                    Core::getLogger()->error('Send email failed. Email: {message}',
                            array('message' => serialize($this->message)));
                    return false;
                }
            }
        } catch (Exception $ex) {
            Core::getLogger()->error('Send email failed. Exception: {exception}',
                    array('exception' => serialize($ex)));
            return false;
        }
    }

}
