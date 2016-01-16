<?php

namespace Admin\Model\Basic;

use THCFrame\Model\Model;

class BasicEmailModel extends Model 
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
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 1
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     * @validate alphanumeric,max(150)
     * @label nazev
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @unique
     * @type varchar
     * @length 200
     * @validate required,alphanumeric,max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 200
     * @validate alphanumeric,max(200)
     * @label subject
     */
    protected $_subject;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate html
     * @label text
     */
    protected $_body;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate html
     * @label text
     */
    protected $_bodyEn;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @label type
     * @default 1
     */
    protected $_type;

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

    public function getActive()
    {
        return $this->_active;
    }

    public function setActive($value)
    {
        $this->_active = $value;
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

    public function getUrlKey()
    {
        return $this->_urlKey;
    }

    public function setUrlKey($value)
    {
        $this->_urlKey = $value;
        return $this;
    }

    public function getSubject()
    {
        return $this->_subject;
    }

    public function setSubject($value)
    {
        $this->_subject = $value;
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

    public function getBodyEn()
    {
        return $this->_bodyEn;
    }

    public function setBodyEn($value)
    {
        $this->_bodyEn = $value;
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