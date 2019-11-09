<?php

namespace THCFrame\Request;

use THCFrame\Bag\AbstractBag;
use THCFrame\Registry\Registry;

/**
 * Description of cookie
 *
 * @author Tomy
 */
class CookieBag extends AbstractBag
{

    /**
     * @var CookieBag
     */
    private static $_instance = null;

    /**
     * Cookie name prefix
     * @var string
     */
    private $_prefix = 'THCF_';

    /**
     * @return CookieBag|static
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new static();
        }

        return self::$_instance;
    }

    private function __construct()
    {
        $this->setName('cookie');
    }

    private function __clone()
    {

    }

    public function clear()
    {
        if (!empty($_COOKIE)) {
            foreach ($_COOKIE as $key => $cookie) {
                unset($_COOKIE[$key]);
                setcookie($this->_prefix . $key, '', time() - 1800, '/', null, false, true);
            }
        }
    }

    public function remove($key)
    {
        $key = $this->hashKey($key);

        if (isset($_COOKIE[$this->_prefix . $key])) {
            unset($_COOKIE[$this->_prefix . $key]);
            setcookie($this->_prefix . $key, '', time() - 1800, '/', null, false, true);
        }
    }

    public function get($key, $default = null)
    {
        $key = $this->hashKey($key);

        if (!empty($_COOKIE[$this->_prefix . $key])) {
            return $_COOKIE[$this->_prefix . $key];
        }

        return $default;
    }

    public function hashKey($key)
    {
        if (ENV === \THCFrame\Core\Core::ENV_LIVE) {
            $secret = Registry::get('configuration')->session->secret;
            return hash_hmac('sha512', $key, $secret);
        } else {
            return $key;
        }
    }

    public function set($key, $value, $exp = null, $path = '/', $domain = null,
            $secure = false, $httponly = true)
    {
        $key = $this->hashKey($key);

        if ($exp === null) {
            $exp = time() + 4 * 3600;
        }

        $_COOKIE[$this->_prefix . $key] = $value;
        setcookie($this->_prefix . $key, $value, $exp, $path, $domain, $secure, $httponly);

        return $this;
    }

}
