<?php
namespace Admin\Model;

use THCFrame\Core\StringMethods;
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
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function checkUrlKey($key): ?bool
    {
        $status = self::first(['urlKey = ?' => $key]);

        return null === $status;
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
        return self::first(['id = ?' => (int) $id]);
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
        return self::first(
                ['id = ?' => (int) $id, 'active = ?' => true, 'type = ?' => 1], [$fieldName, 'subject']);
    }

    public static function fetchActiveByIdAndLang($id, $fieldName)
    {
        return self::first(
                ['id = ?' => (int) $id, 'active = ?' => true], [$fieldName, 'subject']);
    }

    /**
     * @param string $urlKey
     * @param array $data
     *
     * @return null|EmailModel
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function loadAndPrepare($urlKey, $data = []): ?EmailModel
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

        $email->body = StringMethods::prepareEmailText($emailText);

        return $email;
    }

    /**
     * @param array $data
     *
     * @return EmailModel
     */
    public function populate($data = []): EmailModel
    {
        $emailText = str_replace('{MAINURL}', 'https://' . RequestMethods::server('HTTP_HOST'), $this->getBody());

        if (!empty($data)) {
            foreach ($data as $key => $value) {
                $emailText = str_replace($key, $value, $emailText);
            }
        }
        $this->_body = StringMethods::prepareEmailText($emailText);

        return $this;
    }
}
