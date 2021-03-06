<?php

namespace THCFrame\Security\Model;

use THCFrame\Model\Model;

/**
 * Log for api requests and their responses
 */
class ApiRequestLogModel extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'apil';

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
     * @index
     * @type int
     * @length 10
     * @unsigned
     * @null
     *
     * @validate required,numeric, max(10)
     */
    protected $_apiId;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 20
     * @index
     *
     * @validate required, alphanumeric, max(20)
     * @label method
     */
    protected $_requestMethod;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate html
     * @label text
     */
    protected $_apiRequest;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate html
     * @label text
     */
    protected $_apiResponse;

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

}
