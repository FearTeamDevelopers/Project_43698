<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicPartnerModel extends Model 
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
     * @validate required,alphanumeric,max(150)
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 300
     * @validate required,url,max(300)
     * @label web
     */
    protected $_web;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 350
     * @validate path,max(350)
     * @label logo
     */
    protected $_logo;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 30
     * @validate alpha,max(30)
     * @label sekce
     */
    protected $_section;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 3
     * @validate numeric,max(3)
     * @label rank
     * @default 1
     */
    protected $_rank;

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

    public function getWeb()
    {
        return $this->_web;
    }

    public function setWeb($value)
    {
        $this->_web = $value;
        return $this;
    }

    public function getLogo()
    {
        return $this->_logo;
    }

    public function setLogo($value)
    {
        $this->_logo = $value;
        return $this;
    }

    public function getSection()
    {
        return $this->_section;
    }

    public function setSection($value)
    {
        $this->_section = $value;
        return $this;
    }

    public function getRank()
    {
        return $this->_rank;
    }

    public function setRank($value)
    {
        $this->_rank = $value;
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