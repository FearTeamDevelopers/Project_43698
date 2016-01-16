<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicAdvertisementModel extends Model 
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
     * @foreign fk_advertisement_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
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
     * @foreign fk_advertisement_adsection REFERENCES tb_adsection (id) ON DELETE SET NULL ON UPDATE NO ACTION
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label sekce
     * @unsigned
     */
    protected $_sectionId;

    /**
     * @column
     * @readwrite
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label photo
     * @unsigned
     */
    protected $_mainPhotoId;

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
     * @unique
     * @type varchar
     * @length 200
     * @validate required,alphanumeric,max(50)
     * @label jedinečný identifikátor
     */
    protected $_uniqueKey;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 15
     * @validate required,alpha,max(15)
     * @label typ
     */
    protected $_adType;

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
     * @label název
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate required,html
     * @label obsah
     */
    protected $_content;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 20
     * @validate numeric,max(8)
     * @label cena
     */
    protected $_price;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 10
     * @validate date,max(10)
     * @label zobrazovat do
     */
    protected $_expirationDate;

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
     * @type tinyint
     * @length 1
     * @validate max(1)
     */
    protected $_hasAvailabilityRequest;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 130
     * @validate max(130)
     */
    protected $_availabilityRequestToken;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     */
    protected $_availabilityRequestTokenExpiration;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     */
    protected $_state;

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

    public function getSectionId()
    {
        return $this->_sectionId;
    }

    public function setSectionId($value)
    {
        $this->_sectionId = $value;
        return $this;
    }

    public function getMainPhotoId()
    {
        return $this->_mainPhotoId;
    }

    public function setMainPhotoId($value)
    {
        $this->_mainPhotoId = $value;
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

    public function getUniqueKey()
    {
        return $this->_uniqueKey;
    }

    public function setUniqueKey($value)
    {
        $this->_uniqueKey = $value;
        return $this;
    }

    public function getAdType()
    {
        return $this->_adType;
    }

    public function setAdType($value)
    {
        $this->_adType = $value;
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

    public function getContent()
    {
        return $this->_content;
    }

    public function setContent($value)
    {
        $this->_content = $value;
        return $this;
    }

    public function getPrice()
    {
        return $this->_price;
    }

    public function setPrice($value)
    {
        $this->_price = $value;
        return $this;
    }

    public function getExpirationDate()
    {
        return $this->_expirationDate;
    }

    public function setExpirationDate($value)
    {
        $this->_expirationDate = $value;
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

    public function getHasAvailabilityRequest()
    {
        return $this->_hasAvailabilityRequest;
    }

    public function setHasAvailabilityRequest($value)
    {
        $this->_hasAvailabilityRequest = $value;
        return $this;
    }

    public function getAvailabilityRequestToken()
    {
        return $this->_availabilityRequestToken;
    }

    public function setAvailabilityRequestToken($value)
    {
        $this->_availabilityRequestToken = $value;
        return $this;
    }

    public function getAvailabilityRequestTokenExpiration()
    {
        return $this->_availabilityRequestTokenExpiration;
    }

    public function setAvailabilityRequestTokenExpiration($value)
    {
        $this->_availabilityRequestTokenExpiration = $value;
        return $this;
    }

    public function getState()
    {
        return $this->_state;
    }

    public function setState($value)
    {
        $this->_state = $value;
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