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
     * @label prijmeni
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
     * @column
     * @readwrite
     * @type text
     * @length 50
     *
     * @validate alphanumeric, max(50)
     * @label activation token
     */
    protected $_emailActivationToken;

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setLastLogin(0);
            $this->setTotalLoginAttempts(0);
            $this->setLastLoginAttempt(0);
            $this->setFirstLoginAttempt(0);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }
    
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
