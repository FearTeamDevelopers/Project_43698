<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicActionModel extends Model 
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
     * @foreign fk_action_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
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
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 1
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     */
    protected $_approved;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     */
    protected $_archive;

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
     * @label teaser
     */
    protected $_shortBody;

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
     * @type tinyint
     * @length 3
     * @validate numeric,max(3)
     * @label pořadí
     * @default 1
     */
    protected $_rank;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 10
     * @validate required,date,max(10)
     * @label datum začátek
     */
    protected $_startDate;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 10
     * @validate required,date,max(10)
     * @label datum konec
     */
    protected $_endDate;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 8
     * @validate time,max(8)
     * @label čas začátek
     */
    protected $_startTime;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 8
     * @validate time,max(8)
     * @label čas konec
     */
    protected $_endTime;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 350
     * @validate alphanumeric,max(350)
     * @label keywords
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

    public function getApproved()
    {
        return $this->_approved;
    }

    public function setApproved($value)
    {
        $this->_approved = $value;
        return $this;
    }

    public function getArchive()
    {
        return $this->_archive;
    }

    public function setArchive($value)
    {
        $this->_archive = $value;
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

    public function getRank()
    {
        return $this->_rank;
    }

    public function setRank($value)
    {
        $this->_rank = $value;
        return $this;
    }

    public function getStartDate()
    {
        return $this->_startDate;
    }

    public function setStartDate($value)
    {
        $this->_startDate = $value;
        return $this;
    }

    public function getEndDate()
    {
        return $this->_endDate;
    }

    public function setEndDate($value)
    {
        $this->_endDate = $value;
        return $this;
    }

    public function getStartTime()
    {
        return $this->_startTime;
    }

    public function setStartTime($value)
    {
        $this->_startTime = $value;
        return $this;
    }

    public function getEndTime()
    {
        return $this->_endTime;
    }

    public function setEndTime($value)
    {
        $this->_endTime = $value;
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