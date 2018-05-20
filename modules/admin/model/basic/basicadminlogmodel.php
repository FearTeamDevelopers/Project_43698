<?php

namespace Admin\Model\Basic;

use THCFrame\Model\Model;

class BasicAdminlogModel extends Model 
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
     * @type varchar
     * @length 80
     * @validate alphanumeric,max(80)
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 50
     * @validate alpha,max(50)
     */
    protected $_module;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 50
     * @validate alpha,max(50)
     */
    protected $_controller;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 50
     * @validate alpha,max(50)
     */
    protected $_action;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 15
     * @validate alpha,max(15)
     */
    protected $_result;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 250
     * @validate alphanumeric,max(250)
     */
    protected $_httpreferer;

    /**
     * @column
     * @readwrite
     * @type text
     * @validate alphanumeric
     */
    protected $_params;

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

    public function getModule()
    {
        return $this->_module;
    }

    public function setModule($value)
    {
        $this->_module = $value;
        return $this;
    }

    public function getController()
    {
        return $this->_controller;
    }

    public function setController($value)
    {
        $this->_controller = $value;
        return $this;
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function setAction($value)
    {
        $this->_action = $value;
        return $this;
    }

    public function getResult()
    {
        return $this->_result;
    }

    public function setResult($value)
    {
        $this->_result = $value;
        return $this;
    }

    public function getHttpreferer()
    {
        return $this->_httpreferer;
    }

    public function setHttpreferer($value)
    {
        $this->_httpreferer = $value;
        return $this;
    }

    public function getParams()
    {
        return $this->_params;
    }

    public function setParams($value)
    {
        $this->_params = $value;
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