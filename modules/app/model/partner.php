<?php

use THCFrame\Model\Model;

/**
 * 
 */
class App_Model_Partner extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'pa';

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
     * @label title
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate required, url, max(150)
     * @label web
     */
    protected $_web;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * 
     * @validate path, max(200)
     * @label logo
     */
    protected $_logo;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     * 
     * @validate alpha, max(30)
     * @label sekce
     */
    protected $_section;

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
     * @return type
     */
    public function getUnlinkLogoPath($type = true)
    {
        if ($type && !empty($this->_logo)) {
            if (file_exists('./' . $this->_logo)) {
                return './' . $this->_logo;
            } elseif (file_exists('.' . $this->_logo)) {
                return '.' . $this->_logo;
            } elseif (file_exists($this->_logo)) {
                return $this->_logo;
            }
        } else {
            return $this->_logo;
        }
    }

}
