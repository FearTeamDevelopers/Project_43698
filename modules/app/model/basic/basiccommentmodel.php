<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicCommentModel extends Model 
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
     * @foreign fk_comment_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate numeric,max(11)
     * @label id autora
     * @unsigned
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @index
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label id objektu
     * @unsigned
     */
    protected $_resourceId;

    /**
     * @column
     * @readwrite
     * @type int
     * @length 10
     * @unsigned
     */
    protected $_replyTo;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     */
    protected $_type;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate required,html
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

    public function setId($value)
    {
        $this->_id = $value;
        return $this;
    }

    public function getUserId()
    {
        return $this->_userId;
    }

    public function setUserId($value)
    {
        $this->_userId = $value;
        return $this;
    }

    public function getResourceId()
    {
        return $this->_resourceId;
    }

    public function setResourceId($value)
    {
        $this->_resourceId = $value;
        return $this;
    }

    public function getReplyTo()
    {
        return $this->_replyTo;
    }

    public function setReplyTo($value)
    {
        $this->_replyTo = $value;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setType($value)
    {
        $this->_type = $value;
        return $this;
    }

    public function getBody()
    {
        return $this->_body;
    }

    public function setBody($value)
    {
        $this->_body = $value;
        return $this;
    }

    public function getCreated()
    {
        return $this->_created;
    }

    public function setCreated($value)
    {
        $this->_created = $value;
        return $this;
    }

    public function getModified()
    {
        return $this->_modified;
    }

    public function setModified($value)
    {
        $this->_modified = $value;
        return $this;
    }

}