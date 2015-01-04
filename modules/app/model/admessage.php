<?php

use THCFrame\Model\Model;

/**
 * 
 */
class App_Model_AdMessage extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'adm';

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
     * @type text
     * @length 80
     *
     * @validate required, alphanumeric, max(80)
     * @label od
     */
    protected $_msAuthor;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     *
     * @validate required, email, max(60)
     * @label email
     */
    protected $_msEmail;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html
     * @label zpráva
     */
    protected $_message;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     * @label zaslat kopii emailu
     */
    protected $_getEmailCopy;

    /**
     * @column
     * @readwrite
     * @type boolean
     * 
     * @validate max(3)
     */
    protected $_messageSent;

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
            $this->setMessageSent(false);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

}
