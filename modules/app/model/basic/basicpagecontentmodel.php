<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicPagecontentModel extends Model 
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
     * @validate required,alpha,max(150)
     * @label název
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @unique
     * @type varchar
     * @length 200
     * @validate required,alpha,max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type mediumtext
     * @validate required,html
     * @label text
     */
    protected $_body;

    /**
     * @column
     * @readwrite
     * @type mediumtext
     * @validate html
     * @label text en
     */
    protected $_bodyEn;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 350
     * @validate alphanumeric,max(350)
     * @label klíčová slova
     */
    protected $_keywords;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     * @validate alphanumeric,max(150)
     * @label metatitle
     */
    protected $_metaTitle;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate alphanumeric
     * @label metadescription
     */
    protected $_metaDescription;

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

    public function getKeywords()
    {
        return $this->_keywords;
    }

    public function setKeywords($value)
    {
        $this->_keywords = $value;
        return $this;
    }

    public function getMetaTitle()
    {
        return $this->_metaTitle;
    }

    public function setMetaTitle($value)
    {
        $this->_metaTitle = $value;
        return $this;
    }

    public function getMetaDescription()
    {
        return $this->_metaDescription;
    }

    public function setMetaDescription($value)
    {
        $this->_metaDescription = $value;
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