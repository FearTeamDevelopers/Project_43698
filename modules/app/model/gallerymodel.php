<?php

namespace App\Model;

use App\Model\Basic\BasicGalleryModel;

/**
 *
 */
class GalleryModel extends BasicGalleryModel
{

    /**
     * @readwrite
     */
    protected $_alias = 'gl';

    /**
     * @readwrite
     */
    protected $_photos;

    /**
     * @readwrite
     */
    protected $_videos;

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
     * @return array
     */
    public static function fetchAll()
    {
        $query = self::getQuery(['gl.*'])
                ->join('tb_user', 'gl.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     *
     * @return array
     */
    public static function fetchWithLimit($limit = 10)
    {
        $query = self::getQuery(['gl.*'])
                ->join('tb_user', 'gl.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->order('gl.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     *
     * @param type $id
     *
     * @return type
     */
    public static function fetchGalleryById($id)
    {
        $galleryQuery = self::getQuery(['gl.*'])
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', ['ph.imgMain', 'ph.imgThumb'])
                ->where('gl.id = ?', (int) $id);
        $galleryArr = self::initialize($galleryQuery);

        if (!empty($galleryArr)) {
            $gallery = array_shift($galleryArr);

            return $gallery->getAllPhotosForGallery()
                    ->getAllVideosForGallery();
        } else {
            return null;
        }
    }

    /**
     * Called from app module.
     *
     * @param type $urlkey
     *
     * @return type
     */
    public static function fetchPublicActiveGalleryByUrlkey($urlkey)
    {
        $galleryQuery = self::getQuery(['gl.*'])
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', ['ph.imgMain', 'ph.imgThumb'])
                ->where('gl.urlKey = ?', $urlkey)
                ->where('gl.active = ?', true)
                ->where('gl.isPublic = ?', true);
        $galleryArr = self::initialize($galleryQuery);

        if (!empty($galleryArr)) {
            $gallery = array_shift($galleryArr);

            return $gallery;
        } else {
            return null;
        }
    }

    /**
     * Called from app module.
     *
     * @param type $year
     */
    public static function fetchPublicActiveGalleries($limit = 30, $page = 1)
    {
        $query = self::getQuery(['gl.*'])
                ->leftjoin('tb_photo', 'ph.id = gl.avatarPhotoId', 'ph', ['ph.imgMain', 'ph.imgThumb', 'ph.photoName'])
                ->where('gl.active = ?', true)
                ->where('gl.isPublic = ?', true)
                ->order('gl.rank', 'desc')
                ->order('gl.created', 'desc')
                ->limit($limit, $page);

        return self::initialize($query);
    }

    /**
     * Check whether unique identifier already exist or not
     *
     * @param type $urlKey
     * @return boolean
     */
    public static function checkUrlKey($urlKey)
    {
        $status = self::first(['urlKey = ?' => $urlKey]);

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return \App\Model\GalleryModel
     */
    public function getAllPhotosForGallery()
    {
        $photos = \App\Model\PhotoModel::all(['galleryId = ?' => $this->getId()]);

        $this->_photos = $photos;

        return $this;
    }

    /**
     * @return \App\Model\GalleryModel
     */
    public function getActPhotosForGallery()
    {
        $photos = \App\Model\PhotoModel::all(
                        ['galleryId = ?' => $this->getId(), 'active = ?' => true], ['*'], ['rank' => 'desc', 'created' => 'desc']
        );

        $this->_photos = $photos;

        return $this;
    }

    /**
     * @return \App\Model\GalleryModel
     */
    public function getAllVideosForGallery()
    {
        $videos = \App\Model\VideoModel::all(
                        ['galleryId = ?' => $this->getId()], ['*'], ['created' => 'desc']
        );

        $this->_videos = $videos;

        return $this;
    }

    /**
     * @return \App\Model\GalleryModel
     */
    public function getActVideosForGallery()
    {
        $videos = \App\Model\VideoModel::all(
                        ['galleryId = ?' => $this->getId(), 'active = ?' => true], ['*'], ['created' => 'desc']
        );

        $this->_videos = $videos;

        return $this;
    }

}
