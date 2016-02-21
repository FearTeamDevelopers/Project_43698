<?php

namespace THCFrame\Session\Driver;

use THCFrame\Session;

/**
 * Server session class
 */
class Server extends Session\Driver
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
    public function __construct($options = array())
    {
        parent::__construct($options);
        $this->setName('session_server');
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
        if (ENV === 'live') {
            return hash_hmac('sha512', $key, $this->getSecret());
        } else {
            return $key;
        }
    }

    /**
     *
     * @param type $key
     * @param type $default
     * @return type
     */
    public function get($key, $default = null)
    {
        $key = $this->hashKey($key);

        if (isset($_SESSION[$this->prefix . $key])) {
            return $_SESSION[$this->prefix . $key];
        }

        return $default;
    }

    /**
     *
     * @param type $key
     * @param type $value
     * @return \THCFrame\Session\Driver\Server
     */
    public function set($key, $value)
    {
        $key = $this->hashKey($key);

        $_SESSION[$this->prefix . $key] = $value;
        return $this;
    }

    /**
     *
     * @param type $key
     * @return \THCFrame\Session\Driver\Server
     */
    public function remove($key)
    {
        $key = $this->hashKey($key);
        unset($_SESSION[$this->prefix . $key]);
        return $this;
    }

    /**
     *
     * @return \THCFrame\Session\Driver\Server
     */
    public function clear()
    {
        $_SESSION = array();
        return $this;
    }

    public function getName()
    {
        return $this->dataBagName;
    }

    public function setName($name)
    {
        $this->dataBagName = '_databag_' . $name;
        return $this;
    }

}
