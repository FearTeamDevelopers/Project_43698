<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicAdmessageModel extends Model 
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
     * @foreign fk_admessage_advertisement REFERENCES tb_advertisement (id) ON DELETE CASCADE ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate required,numeric,max(11)
     * @unsigned
     */
    protected $_adId;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 80
     * @validate required,alphanumeric,max(80)
     * @label od
     */
    protected $_msAuthor;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 60
     * @validate required,email,max(60)
     * @label email
     */
    protected $_msEmail;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate required,html,max(2048)
     * @label zpráva
     */
    protected $_message;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     * @label zaslat kopii emailu
     */
    protected $_sendEmailCopy;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * @length 1
     * @validate max(1)
     */
    protected $_messageSent;

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

    public function getAdId()
    {
        return $this->_adId;
    }

    public function setAdId($value)
    {
        $this->_adId = $value;
        return $this;
    }

    public function getMsAuthor()
    {
        return $this->_msAuthor;
    }

    public function setMsAuthor($value)
    {
        $this->_msAuthor = $value;
        return $this;
    }

    public function getMsEmail()
    {
        return $this->_msEmail;
    }

    public function setMsEmail($value)
    {
        $this->_msEmail = $value;
        return $this;
    }

    public function getMessage()
    {
        return $this->_message;
    }

    public function setMessage($value)
    {
        $this->_message = $value;
        return $this;
    }

    public function getSendEmailCopy()
    {
        return $this->_sendEmailCopy;
    }

    public function setSendEmailCopy($value)
    {
        $this->_sendEmailCopy = $value;
        return $this;
    }

    public function getMessageSent()
    {
        return $this->_messageSent;
    }

    public function setMessageSent($value)
    {
        $this->_messageSent = $value;
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