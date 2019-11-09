<?php
namespace App\Model;

use App\Model\Basic\BasicPhotoModel;

/**
 *
 */
class PhotoModel extends BasicPhotoModel
{

    public const DEFAULT_PATH_TO_THUMBS = 'public/uploads/images';
    public const DEFAULT_PATH_TO_IMAGES = 'public/uploads/images';

    /**
     * @readwrite
     */
    protected $_alias = 'ph';

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
     * Delete ad record and connected images
     *
     * @return mixed
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public function delete()
    {
        $imgMain = $this->getUnlinkPath();
        $imgThumb = $this->getUnlinkThumbPath();

        $state = parent::delete();

        if ($state != -1) {
            @unlink($imgMain);
            @unlink($imgThumb);
        }

        return $state;
    }

    /**
     * @param string $unit
     * @return float|int|string
     */
    public function getFormatedSize($unit = 'kb')
    {
        $bytes = floatval($this->_size);

        $units = [
            'b' => 1,
            'kb' => 1024,
            'mb' => pow(1024, 2),
            'gb' => pow(1024, 3),
        ];

        $result = $bytes / $units[strtolower($unit)];
        $result = strval(round($result, 2)) . ' ' . strtoupper($unit);

        return $result;
    }

    /**
     * @param bool $type
     * @return string
     */
    public function getUnlinkPath($type = true)
    {
        if ($type && !empty($this->_imgMain)) {
            if (file_exists(APP_PATH . $this->_imgMain)) {
                return APP_PATH . $this->_imgMain;
            } elseif (file_exists('.' . $this->_imgMain)) {
                return '.' . $this->_imgMain;
            } elseif (file_exists('./' . $this->_imgMain)) {
                return './' . $this->_imgMain;
            }
        }

        return $this->_imgMain;
    }

    /**
     * @param bool $type
     * @return string
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type && !empty($this->_imgThumb)) {
            if (file_exists(APP_PATH . $this->_imgThumb)) {
                return APP_PATH . $this->_imgThumb;
            } elseif (file_exists('.' . $this->_imgThumb)) {
                return '.' . $this->_imgThumb;
            } elseif (file_exists('./' . $this->_imgThumb)) {
                return './' . $this->_imgThumb;
            }
        }

        return $this->_imgThumb;
    }

    /**
     * @param $galleryId
     * @param int $limit
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchPhotosByGalleryIdPaged($galleryId, $limit = 30, $page = 1)
    {
        return self::all(
                ['active = ?' => true, 'galleryId = ?' => (int) $galleryId], ['*'], ['rank' => 'desc', 'created' => 'desc'], $limit, $page
        );
    }
}
