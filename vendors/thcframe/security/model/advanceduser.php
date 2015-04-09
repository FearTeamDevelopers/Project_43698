<?php

namespace THCFrame\Security\Model;

use THCFrame\Security\Model\BasicUser;

/**
 * AdvancedUser extends BasicUser functionality
 */
class AdvancedUser extends BasicUser
{
    
    /**
     * @column
     * @readwrite
     * @type integer
     *
     * @validate numeric
     * @label pass expiration time
     */
    protected $_passExpire;
    
    /**
     * @column
     * @readwrite
     * @type integer
     *
     * @validate numeric
     * @label pass expiration time
     */
    protected $_accountExpire;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 20
     *
     * @validate numeric, max(20)
     */
    protected $_lastLoginIp;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     *
     * @validate numeric, max(1000)
     */
    protected $_lastLoginBrowser;
    
    /**
     * To check if the password has aged. i.e. if the time has passed 
     * after which the password must be changed.
     * 
     * @return boolean
     */
    public function isPasswordExpired()
    {
        $currentTime = time();

        if (($currentTime - $this->passExpire) > self::$passwordExpiryTime) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * To check if the account has aged. i.e. if the time has passed 
     * after which the account will be blocked.
     * 
     * @return boolean
     */
    public function isAccountExpired()
    {
        
    }
}
