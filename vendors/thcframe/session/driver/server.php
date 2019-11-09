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
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->setName('session_server');
        @session_start();
    }

    /**
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (isset($_SESSION[$this->prefix . $key])) {
            return $_SESSION[$this->prefix . $key];
        }

        return $default;
    }

    /**
     *
     * @param string $key
     * @param mixed $value
     * @return \THCFrame\Session\Driver\Server
     */
    public function set($key, $value)
    {
        $_SESSION[$this->prefix . $key] = $value;
        return $this;
    }

    /**
     *
     * @param string $key
     * @return \THCFrame\Session\Driver\Server
     */
    public function remove($key)
    {
        unset($_SESSION[$this->prefix . $key]);
        return $this;
    }

    /**
     *
     * @return \THCFrame\Session\Driver\Server
     */
    public function clear()
    {
        $_SESSION = [];
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
