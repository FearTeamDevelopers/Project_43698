<?php

namespace THCFrame\Session\Driver;

use THCFrame\Session;
use THCFrame\Session\Model\Session;

/**
 * Database session class
 */
class Database extends Session\Driver
{

    /**
     * @readwrite
     */
    protected $_prefix;

    /**
     * @readwrite
     */
    protected $_ttl;

    /**
     * @readwrite
     */
    protected $_secret;

    /**
     *
     * @param type $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        session_set_save_handler(
                [$this, 'open'],
                [$this, 'close'],
                [$this, 'get'],
                [$this, 'set'],
                [$this, 'erase'],
                [$this, 'gc']
        );

        @session_start();
    }

    /**
     * Session keys are hashed with sha512 algo
     *
     * @param string $key
     * @return hash
     */
    public function hashKey($key)
    {
        return hash_hmac('sha512', $key, $this->getSecret());
    }

    public function open()
    {
        try{
            $model = new Session();
        } catch (Exception $ex) {

        }
    }

    public function close()
    {

    }

    public function clear()
    {

    }

    /**
     *
     * @param type $key
     */
    public function erase($key)
    {
        $key = $this->hashKey($key);
        $state = Session::deleteAll(['id = ?' => $key]);

        if($state != -1){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * @param type $key
     * @param type $default
     * @return type
     */
    public function get($key, $default = '')
    {
        $key = $this->hashKey($key);
        $ses = Session::first(['id = ?' => $key]);

        if($ses !== null){
            return $ses->getData();
        }else{
            return $default;
        }
    }

    /**
     *
     * @param type $key
     * @param type $value
     * @return boolean
     */
    public function set($key, $value)
    {
        $key = $this->hashKey($key);
        $ses = new Session([
            'id' => $key,
            'expires' => time(),
            'data' => $value
        ]);

        if($ses->validate()){
            $ses->save();
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * @param type $max
     * @return boolean
     */
    public function gc($max)
    {
        $max = $this->getTtl();
        $old = time() - $max;

        $state = Session::deleteAll(['expires < ?' => $old]);

        if($state != -1){
            return true;
        }else{
            return false;
        }
    }

}
