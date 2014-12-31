<?php

use THCFrame\Security\Model\BasicUser;

/**
 * 
 */
class App_Model_User extends BasicUser
{

    /**
     * @column
     * @readwrite
     * @type text
     * @length 40
     *
     * @validate required, alpha, min(3), max(40)
     * @label jméno
     */
    protected $_firstname;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 40
     *
     * @validate required, alpha, min(3), max(40)
     * @label příjmení
     */
    protected $_lastname;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     * 
     * @validate numeric, max(15)
     * @label telefon
     */
    protected $_phoneNumber;

    /**
     * 
     * @return type
     */
    public function getWholeName()
    {
        return $this->_firstname . ' ' . $this->_lastname;
    }

    /**
     * 
     * @return type
     */
    public function __toString()
    {
        $str = "Id: {$this->_id} <br/>Email: {$this->_email} <br/> Name: {$this->_firstname} {$this->_lastname}";
        return $str;
    }
    
}
