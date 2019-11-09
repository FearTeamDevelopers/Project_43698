<?php

namespace THCFrame\Session\Driver;

use THCFrame\Session;
use THCFrame\Session\Model\Session as SessionModel;

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
     * @param array $options
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

    public function open()
    {
        try{
            $model = new SessionModel();
        } catch (\Exception $ex) {

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
     * @param string $key
     * @return bool
     */
    public function erase($key)
    {
        $state = SessionModel::deleteAll(['id = ?' => $key]);

        if($state != -1){
            return true;
        }else{
            return false;
        }
    }

    /**
     *
     * @param string $key
     * @param mixed$default
     * @return mixed
     */
    public function get($key, $default = '')
    {
        $ses = SessionModel::first(['id = ?' => $key]);

        if($ses !== null){
            return $ses->getData();
        }else{
            return $default;
        }
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return boolean
     * @throws \THCFrame\Model\Exception\Validation
     */
    public function set($key, $value)
    {
        $ses = new SessionModel([
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

        $state = SessionModel::deleteAll(['expires < ?' => $old]);

        if($state != -1){
            return true;
        }else{
            return false;
        }
    }

}
