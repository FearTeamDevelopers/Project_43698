<?php

namespace App\Model\Basic;

use THCFrame\Model\Model;

class BasicAttendanceModel extends Model 
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
     * @foreign fk_attendance_user REFERENCES tb_user (id) ON DELETE SET NULL ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate numeric,max(11)
     * @label id uzivatele
     * @unsigned
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @foreign fk_attendance_action REFERENCES tb_action (id) ON DELETE CASCADE ON UPDATE NO ACTION
     * @type int
     * @length 11
     * @validate numeric,max(11)
     * @label id akce
     * @unsigned
     */
    protected $_actionId;

    /**
     * @column
     * @readwrite
     * @index
     * @type tinyint
     * @length 1
     * @validate max(1)
     */
    protected $_type;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 250
     * @validate alphanumeric,max(350)
     * @label comment
     */
    protected $_comment;

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

    public function getUserId()
    {
        return $this->_userId;
    }

    public function setUserId($value)
    {
        $this->_userId = $value;
        return $this;
    }

    public function getActionId()
    {
        return $this->_actionId;
    }

    public function setActionId($value)
    {
        $this->_actionId = $value;
        return $this;
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setType($value)
    {
        $this->_type = $value;
        return $this;
    }

    public function getComment()
    {
        return $this->_comment;
    }

    public function setComment($value)
    {
        $this->_comment = $value;
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