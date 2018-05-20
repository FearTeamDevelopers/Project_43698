<?php

namespace THCFrame\Registry;

/**
 * Registry is a Singleton, used to store instance of other 'normal' classes.
 * Instances of non-Singleton classes are given a key (identifier) and 
 * are 'kept' inside the Registry’s private storage
 */
class Registry
{

    /**
     * Object instances and variables
     * 
     * @var array
     */
    private static $instances = [];

    private function __construct()
    {
        
    }

    private function __clone()
    {
        
    }

    /**
     * The get() method searches the private storage for an 
     * instance with a matching key. If it finds an instance, it will 
     * return it, or default to the value supplied with the $default parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        if (isset(self::$instances[$key])) {
            return self::$instances[$key];
        }
        return $default;
    }

    /**
     * The set() method is used to “store” an instance with a specified 
     * key in the registry’s private storage.
     * 
     * @param string $key
     * @param mixed $instance
     */
    public static function set($key, $instance = null)
    {
        self::$instances[$key] = $instance;
    }

    /**
     * The erase() method is useful for removing an instance at a certain key
     * 
     * @param string $key
     */
    public static function erase($key)
    {
        unset(self::$instances[$key]);
    }

}
