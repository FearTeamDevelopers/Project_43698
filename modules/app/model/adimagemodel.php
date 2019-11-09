<?php

namespace App\Model;

use App\Model\Basic\BasicAdimageModel;

/**
 * 
 */
class AdImageModel extends BasicAdimageModel
{

    /**
     * @readwrite
     */
    protected $_alias = 'adi';

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
        }
        $this->setModified(date('Y-m-d H:i:s'));
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

}
