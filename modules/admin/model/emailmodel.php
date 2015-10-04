<?php

namespace Admin\Model;

use THCFrame\Model\Model;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * Email template ORM class.
 */
class EmailModel extends Model
{

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate alphanumeric, max(150)
     * @label nazev
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * @unique
     * 
     * @validate required, alphanumeric, max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * 
     * @validate alphanumeric, max(200)
     * @label subject
     */
    protected $_subject;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate html
     * @label text
     */
    protected $_body;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate html
     * @label text
     */
    protected $_bodyEn;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * 
     * @validate numeric, max(2)
     * @label type
     */
    protected $_type;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_modified;

    /**
     * @readwrite
     *
     * @var array
     */
    protected $_recipients = array();

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    public function getSubjectWithPrefix()
    {
        if (ENV != 'live') {
            return '[TEST] ' . $this->_subject;
        }

        return $this->_subject;
    }

    public static function fetchAll()
    {
        return self::all();
    }

    public static function fetchAllCommon()
    {
        return self::all(array('type = ?' => 1));
    }

    public static function fetchById($id)
    {
        return \Admin\Model\EmailModel::first(array('id = ?' => (int) $id));
    }

    public static function fetchAllActive()
    {
        return self::all(array('active = ?' => true));
    }

    public static function fetchAllCommonActive()
    {
        return self::all(array('active = ?' => true, 'type = ?' => 1));
    }

    public static function fetchCommonActiveByIdAndLang($id, $fieldName)
    {
        return \Admin\Model\EmailModel::first(
                        array('id = ?' => (int) $id, 'active = ?' => true, 'type = ?' => 1), array($fieldName, 'subject'));
    }

    public static function fetchActiveByIdAndLang($id, $fieldName)
    {
        return \Admin\Model\EmailModel::first(
                        array('id = ?' => (int) $id, 'active = ?' => true), array($fieldName, 'subject'));
    }

    /**
     * @param type $urlKey
     * @param type $data
     *
     * @return type
     */
    public static function loadAndPrepare($urlKey, $data = array())
    {
        $email = self::first(array('urlKey = ?' => $urlKey));

        if (empty($email)) {
            return;
        }

        $emailText = str_replace('{MAINURL}', 'http://' . RequestMethods::server('HTTP_HOST'), $email->getBody());

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $emailText = str_replace($key, $value, $emailText);
            }
        }

        $email->_body = $emailText;

        return $email;
    }

    /**
     * @param type $data
     *
     * @return \Admin\Model\EmailModel
     */
    public function populate($data = array())
    {
        $emailText = str_replace('{MAINURL}', 'http://' . RequestMethods::server('HTTP_HOST'), $this->getBody());

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $emailText = str_replace($key, $value, $emailText);
            }
        }

        $this->_body = $emailText;

        return $this;
    }

    /**
     * @param type $sendTo
     * @param type $sendFrom
     * @param type $oneByOne
     *
     * @return bool
     */
    public function send($oneByOne = false, $sendFrom = null)
    {
        try {
            require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
            $transport = \Swift_MailTransport::newInstance();
            $mailer = \Swift_Mailer::newInstance($transport);

            $message = \Swift_Message::newInstance(null)
                    ->setSubject($this->getSubjectWithPrefix())
                    ->setBody($this->getBody(), 'text/html');

            if (null === $sendFrom) {
                $defaultEmail = Registry::get('configuration')->system->defaultemail;
                $message->setFrom($defaultEmail);
            } else {
                if (!$this->_validateEmail($sendFrom)) {
                    return false;
                }
                $message->setFrom($sendFrom);
            }

            if ($oneByOne === true) {
                if (empty($this->_recipients)) {
                    $adminEmail = Registry::get('configuration')->system->adminemail;
                    $defaultEmail = Registry::get('configuration')->system->defaultemail;
                    $this->_recipients = array($adminEmail, $defaultEmail);
                }

                $error = false;
                foreach ($this->_recipients as $recipient) {
                    $message->setTo($recipient);

                    if ($mailer->send($message)) {
                        Event::fire('admin.log', array('success', sprintf('Email send to %s', $recipient)));
                    } else {
                        $error = true;
                        Event::fire('admin.log', array('fail', 'No email sent'));
                    }
                }

                return $error;
            } else {
                if (empty($this->_recipients)) {
                    $adminEmail = Registry::get('configuration')->system->adminemail;
                    $defaultEmail = Registry::get('configuration')->system->defaultemail;
                    $this->_recipients = array($adminEmail, $defaultEmail);
                }

                $message->setTo($this->_recipients);

                if ($mailer->send($message)) {
                    return true;
                } else {
                    Event::fire('admin.log', array('fail', 'No email sent'));

                    return false;
                }
            }
        } catch (\Exception $ex) {
            Event::fire('admin.log', array('fail', 'Error while sending email: ' . $ex->getMessage()));

            return false;
        }
    }

    public function setRecipient($email)
    {
        if (!empty($email) && $this->_validateEmail($email)) {
            $this->_recipients[] = $email;
        }

        return $this;
    }

    public function setRecipients(array $emails)
    {
        foreach ($emails as $email) {
            if (!empty($email) && $this->_validateEmail($email)) {
                $this->_recipients[] = $email;
            }
        }

        return $this;
    }

    public function getRecipients()
    {
        return $this->_recipients;
    }

    public function getRecipientsToString($glue = ';')
    {
        if (!empty($this->_recipients)) {
            return implode($glue, $this->_recipients);
        } else {
            return '';
        }
    }

}
