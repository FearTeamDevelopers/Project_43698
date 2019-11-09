<?php

namespace App\Model;

use App\Model\Basic\BasicGalleryModel;
use THCFrame\Filesystem\FileManager;
use THCFrame\Registry\Registry;

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
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
     * @param int $limit
     * @return array
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
     * @param $id
     * @return |null |null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
     * @param $urlkey
     * @return mixed|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
            return array_shift($galleryArr);
        } else {
            return null;
        }
    }

    /**
     * Called from app module.
     *
     * @param int $limit
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function checkUrlKey($urlKey)
    {
        $status = self::first(['urlKey = ?' => $urlKey]);

        return null === $status;
    }

    /**
     *
     * @param int $id
     * @param bool $keepGalleryDir
     * @throws \THCFrame\Filesystem\Exception\IO
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function deleteAllPhotos(int $id, bool $keepGalleryDir = false)
    {
        $gallery = static::first(
                ['id = ?' => $id], ['id', 'title', 'created', 'userId', 'urlKey']
        );
        if (null !== $gallery) {
            $fm = new FileManager();
            $configuration = Registry::get('configuration');

            if (!empty($configuration->files)) {
                $pathToImages = trim($configuration->files->pathToImages, '/');
                $pathToThumbs = trim($configuration->files->pathToThumbs, '/');
            } else {
                $pathToImages = PhotoModel::DEFAULT_PATH_TO_IMAGES;
                $pathToThumbs = PhotoModel::DEFAULT_PATH_TO_THUMBS;
            }

            $photos = PhotoModel::all(['galleryId = ?' => $id], ['id']);

            if (!empty($photos)) {
                $ids = [];
                foreach ($photos as $colPhoto) {
                    $ids[] = $colPhoto->getId();
                }

                PhotoModel::deleteAll(['id IN ?' => $ids]);

                $path = APP_PATH . '/' . $pathToImages . '/gallery/' . $gallery->getUrlKey();
                $pathThumbs = APP_PATH . '/' . $pathToThumbs . '/gallery/' . $gallery->getUrlKey();

                if ($path === $pathThumbs) {
                    $fm->remove($path);
                } else {
                    $fm->remove($path);
                    $fm->remove($pathThumbs);
                }
                
                if($keepGalleryDir === true){
                    $fm->mkdir($path);
                    $fm->mkdir($pathThumbs);
                }
            }
        }
    }

    /**
     * @return \App\Model\GalleryModel
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public function getAllPhotosForGallery()
    {
        $photos = \App\Model\PhotoModel::all(['galleryId = ?' => $this->getId()]);

        $this->_photos = $photos;

        return $this;
    }

    /**
     * @return \App\Model\GalleryModel
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
