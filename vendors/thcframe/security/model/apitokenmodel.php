<?php

namespace THCFrame\Security\Model;

use THCFrame\Model\Model;
use THCFrame\Core\Rand;

/**
 * Authtoken used for authentification api requests
 */
class ApiTokenModel extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'apit';

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     * @unsigned
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @index
     * @type int
     * @length 10
     * @unsigned
     * @null
     *
     * @validate required,numeric, max(10)
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 130
     * @index
     * @unique
     *
     * @validate required, alphanumeric, max(130)
     * @label auth token
     */
    protected $_token;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @null
     *
     * @default null
     * @validate datetime, max(19)
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @null
     *
     * @default null
     * @validate datetime, max(19)
     */
    protected $_modified;

    /**
     *
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary["raw"];

        if (empty($this->$raw)) {
            $this->setCreated(date("Y-m-d H:i:s"));
        }
        $this->setModified(date("Y-m-d H:i:s"));
    }

    /**
     * Generates api token
     *
     * @return string
     */
    public static function generateToken($length = 128)
    {
        $token = Rand::randStr($length);

        $tokenExists = self::first(['token = ?' => $token]);

        if ($tokenExists !== null) {
            for ($i = 0; $i <= 100; $i+=1) {
                $token = Rand::randStr($length);
                $tokenExists = self::first(['token = ?' => $token]);

                if ($tokenExists === null) {
                    break;
                }
            }
        }

        return $token;
    }

}
