<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicVideoModel extends Model
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
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label gallery id
     * @unsigned
     */
    protected $_galleryId;

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
     * @unique
     * @type varchar
     * @length 200
     * @validate required,max(200)
     * @label url
     */
    protected $_url;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 50
     * @validate alphanumeric,max(200)
     * @label videoCode
     */
    protected $_videoCode;

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

    public function getGalleryId()
    {
        return $this->_galleryId;
    }

    public function getActive()
    {
        return $this->_active;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getVideoCode()
    {
        return $this->_videoCode;
    }

    public function getCreated()
    {
        return $this->_created;
    }

    public function getModified()
    {
        return $this->_modified;
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    public function setGalleryId($galleryId)
    {
        $this->_galleryId = $galleryId;
        return $this;
    }

    public function setActive($active)
    {
        $this->_active = $active;
        return $this;
    }

    public function setUrl($url)
    {
        $this->_url = $url;
        return $this;
    }

    public function setVideoCode($videoCode)
    {
        $this->_videoCode = $videoCode;
        return $this;
    }

    public function setCreated($created)
    {
        $this->_created = $created;
        return $this;
    }

    public function setModified($modified)
    {
        $this->_modified = $modified;
        return $this;
    }

}
