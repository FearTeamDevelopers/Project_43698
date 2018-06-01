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

    /**
     * Check whether action unique identifier already exist or not.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function checkUrlKey($key)
    {
        $status = self::first(['urlKey = ?' => $key]);

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }

    public static function fetchAll()
    {
        return self::all();
    }

    public static function fetchAllCommon()
    {
        return self::all(['type = ?' => 1]);
    }

    public static function fetchById($id)
    {
        return \Admin\Model\EmailModel::first(['id = ?' => (int) $id]);
    }

    public static function fetchAllActive()
    {
        return self::all(['active = ?' => true]);
    }

    public static function fetchAllCommonActive()
    {
        return self::all(['active = ?' => true, 'type = ?' => 1]);
    }

    public static function fetchCommonActiveByIdAndLang($id, $fieldName)
    {
        return \Admin\Model\EmailModel::first(
                        ['id = ?' => (int) $id, 'active = ?' => true, 'type = ?' => 1], [$fieldName, 'subject']);
    }

    public static function fetchActiveByIdAndLang($id, $fieldName)
    {
        return \Admin\Model\EmailModel::first(
                        ['id = ?' => (int) $id, 'active = ?' => true], [$fieldName, 'subject']);
    }

    /**
     * @param string $urlKey
     * @param array $data
     *
     * @return null|\Admin\Model\EmailModel
     */
    public static function loadAndPrepare($urlKey, $data = [])
    {
        $email = self::first(['urlKey = ?' => $urlKey]);

        if (empty($email)) {
            return null;
        }

        $emailText = str_replace('{MAINURL}', 'https://' . RequestMethods::server('HTTP_HOST'), $email->getBody());

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $emailText = str_replace($key, $value, $emailText);
            }
        }

        $email->body = $emailText;

        return $email;
    }

    /**
     * @param array $data
     *
     * @return \Admin\Model\EmailModel
     */
    public function populate($data = [])
    {
        $emailText = str_replace('{MAINURL}', 'https://' . RequestMethods::server('HTTP_HOST'), $this->getBody());

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $emailText = str_replace($key, $value, $emailText);
            }
        }

        $this->_body = $emailText;

        return $this;
    }

}
