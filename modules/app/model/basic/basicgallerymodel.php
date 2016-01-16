<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicGalleryModel extends Model 
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
     * @foreign fk_gallery_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate numeric,max(11)
     * @unsigned
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @unsigned
     */
    protected $_avatarPhotoId;

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
     * @label název
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate required,html,max(1024)
     * @label popis
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @default 1
     */
    protected $_isPublic;

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

    public function getUserId()
    {
        return $this->_userId;
    }

    public function setUserId($value)
    {
        $this->_userId = $value;
        return $this;
    }

    public function getAvatarPhotoId()
    {
        return $this->_avatarPhotoId;
    }

    public function setAvatarPhotoId($value)
    {
        $this->_avatarPhotoId = $value;
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

    public function getDescription()
    {
        return $this->_description;
    }

    public function setDescription($value)
    {
        $this->_description = $value;
        return $this;
    }

    public function getIsPublic()
    {
        return $this->_isPublic;
    }

    public function setIsPublic($value)
    {
        $this->_isPublic = $value;
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