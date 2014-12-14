<?php

use THCFrame\Model\Model;

/**
 * 
 */
class App_Model_Gallery extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'gl';

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(8)
     */
    protected $_userId;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(8)
     */
    protected $_avatarPhotoId;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate required, alphanumeric, max(150)
     * @label název
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * @unique
     * 
     * @validate required, alphanumeric, max(200)
     * @label url key
     */
    protected $_urlKey;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 80
     * 
     * @validate alphanumeric, max(80)
     * @label alias autora
     */
    protected $_userAlias;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * 
     * @validate numeric, max(2)
     * @label rank
     */
    protected $_rank;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html
     * @label popis
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(2)
     * @lable veřejná-soukromá
     */
    protected $_isPublic;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(2)
     * @lable systémová
     */
    protected $_isSystem;
    
    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;

    /**
     * @readwrite
     */
    protected $_photos;

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * 
     * @return array
     */
    public static function fetchAll()
    {
        $query = self::getQuery(array('gl.*'))
                ->join('tb_user', 'gl.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'));
        
        return self::initialize($query);
    }

    /**
     * Called from admin module
     * 
     * @param type $id
     * @return type
     */
    public static function fetchGalleryById($id)
    {
        $galleryQuery = self::getQuery(array('gl.*'))
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', 
                        array('ph.imgMain', 'ph.imgThumb'))
                ->where('gl.id = ?', (int) $id);
        $galleryArr = self::initialize($galleryQuery);

        if (!empty($galleryArr)) {
            $gallery = array_shift($galleryArr);
            return $gallery->getAllPhotosForGallery();
        } else {
            return null;
        }
    }

    /**
     * Called from app module
     * 
     * @param type $urlkey
     * @return type
     */
    public static function fetchPublicActiveGalleryByUrlkey($urlkey)
    {
        $galleryQuery = self::getQuery(array('gl.*'))
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', 
                        array('ph.imgMain', 'ph.imgThumb'))
                ->where('gl.urlKey = ?', $urlkey)
                ->where('gl.active = ?', true)
                ->where('gl.isPublic = ?', true);
        $galleryArr = self::initialize($galleryQuery);

        if (!empty($galleryArr)) {
            $gallery = array_shift($galleryArr);
            return $gallery->getActPhotosForGallery();
        } else {
            return null;
        }
    }

    /**
     * Called from app module
     * 
     * @param type $year
     */
    public static function fetchPublicActiveGalleries()
    {
        $query = self::getQuery(array('gl.*'))
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', 
                        array('ph.imgMain', 'ph.imgThumb'))
                ->where('gl.active = ?', true)
                ->where('gl.isPublic = ?', true)
                ->order('gl.rank', 'desc')
                ->order('gl.created', 'desc');

        return self::initialize($query);
    }

    /**
     * 
     * @return \App_Model_Gallery
     */
    public function getAllPhotosForGallery()
    {
        $photos = App_Model_Photo::all(array('galleryId = ?' => $this->getId()));

        $this->_photos = $photos;

        return $this;
    }

    /**
     * 
     * @return \App_Model_Gallery
     */
    public function getActPhotosForGallery()
    {
        $photos = App_Model_Photo::all(
                array('galleryId = ?' => $this->getId(), 'active = ?' => true), 
                array('*'),
                array('rank' => 'desc', 'created' => 'desc')
        );

        $this->_photos = $photos;

        return $this;
    }
}
