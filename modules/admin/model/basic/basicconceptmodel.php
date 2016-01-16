<?php

namespace Admin\Model\Basic;

use THCFrame\Model\Model;

class BasicConceptModel extends Model 
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
     * @foreign fk_concept_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
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
     * @label typ
     */
    protected $_type;

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
     * @type text
     * @validate html
     * @label teaser
     */
    protected $_shortBody;

    /**
     * @column
     * @readwrite
     * @type mediumtext
     * @validate html
     * @label text
     */
    protected $_body;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 250
     * @validate alphanumeric,max(250)
     * @label keywords
     */
    protected $_keywords;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     * @validate alphanumeric,max(150)
     * @label meta
     */
    protected $_metaTitle;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate alphanumeric
     * @label meta
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

    public function getUserId()
    {
        return $this->_userId;
    }

    public function setUserId($value)
    {
        $this->_userId = $value;
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

    public function getTitle()
    {
        return $this->_title;
    }

    public function setTitle($value)
    {
        $this->_title = $value;
        return $this;
    }

    public function getShortBody()
    {
        return $this->_shortBody;
    }

    public function setShortBody($value)
    {
        $this->_shortBody = $value;
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