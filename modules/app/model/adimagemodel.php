<?php

namespace App\Model;

use THCFrame\Model\Model;

/**
 * 
 */
class AdImageModel extends Model
{
    /**
     * @readwrite
     */
    protected $_alias = 'adi';

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
     * @index
     * 
     * @validate required, numeric, max(8)
     */
    protected $_adId;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     * 
     * @validate required, numeric, max(8)
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * 
     * @validate alphanumeric, max(60)
     * @label název fotky
     */
    protected $_photoName;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 350
     * 
     * @validate required, path, max(350)
     * @label photo path
     */
    protected $_imgMain;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 350
     * 
     * @validate required, path, max(350)
     * @label thumb path
     */
    protected $_imgThumb;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_modified;

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
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type && !empty($this->_imgMain)) {
            if (file_exists(APP_PATH.$this->_imgMain)) {
                return APP_PATH.$this->_imgMain;
            } elseif (file_exists('.'.$this->_imgMain)) {
                return '.'.$this->_imgMain;
            } elseif (file_exists('./'.$this->_imgMain)) {
                return './'.$this->_imgMain;
            }
        } else {
            return $this->_imgMain;
        }
    }

    /**
     * @return type
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type && !empty($this->_imgThumb)) {
            if (file_exists(APP_PATH.$this->_imgThumb)) {
                return APP_PATH.$this->_imgThumb;
            } elseif (file_exists('.'.$this->_imgThumb)) {
                return '.'.$this->_imgThumb;
            } elseif (file_exists('./'.$this->_imgThumb)) {
                return './'.$this->_imgThumb;
            }
        } else {
            return $this->_imgThumb;
        }
    }
  
    /**
     * Delete ad record and connected images
     * 
     * @return type
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
