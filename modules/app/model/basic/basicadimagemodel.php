<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicAdimageModel extends Model 
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
     * @foreign fk_adimage_advertisement REFERENCES tb_advertisement (id) ON DELETE CASCADE ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate required,numeric,max(11)
     * @unsigned
     */
    protected $_adId;

    /**
     * @column
     * @readwrite
     * @foreign fk_adimage_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate required,numeric,max(11)
     * @unsigned
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 60
     * @validate alphanumeric,max(60)
     * @label název fotky
     */
    protected $_photoName;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 350
     * @validate required,path,max(350)
     * @label thumb path
     */
    protected $_imgThumb;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 350
     * @validate required,path,max(350)
     * @label photo path
     */
    protected $_imgMain;

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

    public function getAdId()
    {
        return $this->_adId;
    }

    public function setAdId($value)
    {
        $this->_adId = $value;
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

    public function getPhotoName()
    {
        return $this->_photoName;
    }

    public function setPhotoName($value)
    {
        $this->_photoName = $value;
        return $this;
    }

    public function getImgThumb()
    {
        return $this->_imgThumb;
    }

    public function setImgThumb($value)
    {
        $this->_imgThumb = $value;
        return $this;
    }

    public function getImgMain()
    {
        return $this->_imgMain;
    }

    public function setImgMain($value)
    {
        $this->_imgMain = $value;
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