<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicPhotoModel extends Model 
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
     * @foreign fk_photo_gallery REFERENCES tb_gallery (id) ON DELETE CASCADE ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate numeric,max(11)
     * @unsigned
     */
    protected $_galleryId;

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
     * @length 250
     * @validate required,path,max(250)
     * @label thumb path
     */
    protected $_imgThumb;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 250
     * @validate required,path,max(250)
     * @label photo path
     */
    protected $_imgMain;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate alphanumeric,max(500)
     * @label popis
     */
    protected $_description;

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
     * @type varchar
     * @length 32
     * @validate required,max(32)
     * @label mime type
     */
    protected $_mime;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 10
     * @validate required,alpha,max(10)
     * @label format
     */
    protected $_format;

    /**
     * @column
     * @readwrite
     * @type int
     * @length 10
     * @validate required,numeric,max(10)
     * @label size
     */
    protected $_size;

    /**
     * @column
     * @readwrite
     * @type int
     * @length 5
     * @validate required,numeric,max(5)
     * @label width
     */
    protected $_width;

    /**
     * @column
     * @readwrite
     * @type int
     * @length 5
     * @validate required,numeric,max(5)
     * @label height
     */
    protected $_height;

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

    public function getGalleryId()
    {
        return $this->_galleryId;
    }

    public function setGalleryId($value)
    {
        $this->_galleryId = $value;
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

    public function getDescription()
    {
        return $this->_description;
    }

    public function setDescription($value)
    {
        $this->_description = $value;
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

    public function getMime()
    {
        return $this->_mime;
    }

    public function setMime($value)
    {
        $this->_mime = $value;
        return $this;
    }

    public function getFormat()
    {
        return $this->_format;
    }

    public function setFormat($value)
    {
        $this->_format = $value;
        return $this;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function setSize($value)
    {
        $this->_size = $value;
        return $this;
    }

    public function getWidth()
    {
        return $this->_width;
    }

    public function setWidth($value)
    {
        $this->_width = $value;
        return $this;
    }

    public function getHeight()
    {
        return $this->_height;
    }

    public function setHeight($value)
    {
        $this->_height = $value;
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