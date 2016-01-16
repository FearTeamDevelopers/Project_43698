<?php

namespace Admin\Model;

use THCFrame\Request\RequestMethods;
use Admin\Model\Basic\BasicEmailModel;

/**
 * Email template ORM class.
 */
class EmailModel extends BasicEmailModel
{

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

}
