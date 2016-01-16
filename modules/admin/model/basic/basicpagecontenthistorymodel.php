<?php

namespace Admin\Model\Basic;

use THCFrame\Model\Model;

class BasicPagecontenthistoryModel extends Model 
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
     * @label source id
     * @unsigned
     */
    protected $_originId;

    /**
     * @column
     * @readwrite
     * @index
     * @type int
     * @length 10
     * @validate numeric,max(10)
     * @label editor id
     * @unsigned
     */
    protected $_editedBy;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 30
     * @validate alphanumeric,max(30)
     * @label remote
     */
    protected $_remoteAddr;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     * @validate url,max(150)
     * @label referrer
     */
    protected $_referer;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate alphanumeric
     * @label changes
     */
    protected $_changedData;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @validate datetime,max(19)
     */
    protected $_created;

    public function getId()
    {
        return $this->_id;
    }

    public function setId($value)
    {
        $this->_id = $value;
        return $this;
    }

    public function getOriginId()
    {
        return $this->_originId;
    }

    public function setOriginId($value)
    {
        $this->_originId = $value;
        return $this;
    }

    public function getEditedBy()
    {
        return $this->_editedBy;
    }

    public function setEditedBy($value)
    {
        $this->_editedBy = $value;
        return $this;
    }

    public function getRemoteAddr()
    {
        return $this->_remoteAddr;
    }

    public function setRemoteAddr($value)
    {
        $this->_remoteAddr = $value;
        return $this;
    }

    public function getReferer()
    {
        return $this->_referer;
    }

    public function setReferer($value)
    {
        $this->_referer = $value;
        return $this;
    }

    public function getChangedData()
    {
        return $this->_changedData;
    }

    public function setChangedData($value)
    {
        $this->_changedData = $value;
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

}