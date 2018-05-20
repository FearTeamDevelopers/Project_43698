<?php

namespace Admin\Model\Basic;

use THCFrame\Model\Model;

class BasicImessageModel extends Model 
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
     * @foreign fk_imessage_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate numeric,max(11)
     * @label autor
     * @unsigned
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 1
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @index
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @label typ
     * @default 1
     */
    protected $_messageType;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 80
     * @validate alphanumeric,max(80)
     * @label alias autora
     */
    protected $_userAlias;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     * @validate required,alphanumeric,max(150)
     * @label nazev
     */
    protected $_title;

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
     * @length 10
     * @validate date,max(10)
     * @label zobrazovat od
     */
    protected $_displayFrom;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 10
     * @validate date,max(10)
     * @label zobrazovat do
     */
    protected $_displayTo;

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

    public function getActive()
    {
        return $this->_active;
    }

    public function setActive($value)
    {
        $this->_active = $value;
        return $this;
    }

    public function getMessageType()
    {
        return $this->_messageType;
    }

    public function setMessageType($value)
    {
        $this->_messageType = $value;
        return $this;
    }

    public function getUserAlias()
    {
        return $this->_userAlias;
    }

    public function setUserAlias($value)
    {
        $this->_userAlias = $value;
        return $this;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function setTitle($value)
    {
        $this->_title = $value;
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

    public function getDisplayFrom()
    {
        return $this->_displayFrom;
    }

    public function setDisplayFrom($value)
    {
        $this->_displayFrom = $value;
        return $this;
    }

    public function getDisplayTo()
    {
        return $this->_displayTo;
    }

    public function setDisplayTo($value)
    {
        $this->_displayTo = $value;
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