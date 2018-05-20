<?php

namespace THCFrame\Logger\Model;

use THCFrame\Model\Model;

class LoggerModel extends Model
{

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
     * @type varchar
     * @length 20
     * @validate required,alpha,max(20)
     * @label log level
     */
    protected $_level;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     * @validate required,alpha,max(150)
     * @label log identifier
     */
    protected $_identifier;

    /**
     * @column
     * @readwrite
     * @type mediumtext
     * @validate required
     * @label text
     */
    protected $_body;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     */
    protected $_modified;

    public function getId()
    {
        return $this->_id;
    }

    public function getUserId()
    {
        return $this->_userId;
    }

    public function getLevel()
    {
        return $this->_level;
    }

    public function getIdentifier()
    {
        return $this->_identifier;
    }

    public function getContext()
    {
        return $this->_context;
    }

    public function getBody()
    {
        return $this->_body;
    }

    public function getCreated()
    {
        return $this->_created;
    }

    public function getModified()
    {
        return $this->_modified;
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    public function setUserId($userId)
    {
        $this->_userId = $userId;
        return $this;
    }

    public function setLevel($level)
    {
        $this->_level = $level;
        return $this;
    }

    public function setIdentifier($identifier)
    {
        $this->_identifier = $identifier;
        return $this;
    }

    public function setContext($context)
    {
        $this->_context = $context;
        return $this;
    }

    public function setBody($body)
    {
        $this->_body = $body;
        return $this;
    }

    public function setCreated($created)
    {
        $this->_created = $created;
        return $this;
    }

    public function setModified($modified)
    {
        $this->_modified = $modified;
        return $this;
    }

}
