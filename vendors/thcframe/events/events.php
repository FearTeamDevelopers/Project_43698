<?php

namespace THCFrame\Events;

/**
 * Event listener
 */
class Events
{

    private static $callbacks = [];

    private function __construct()
    {

    }

    private function __clone()
    {

    }

    /**
     * Add new event and callback function
     *
     * @param string $type
     * @param callable $callback
     */
    public static function add($type, $callback)
    {
        if (empty(self::$callbacks[$type])) {
            self::$callbacks[$type] = [];
        }
        self::$callbacks[$type][] = $callback;
    }

    /**
     * Call specific event callback function with provided parameters
     *
     * @param string $type
     * @param mixed $parameters
     */
    public static function fire($type, $parameters = null)
    {
        if (!empty(self::$callbacks[$type])) {
            foreach (self::$callbacks[$type] as $callback) {
                call_user_func_array($callback, $parameters);
            }
        }
    }

    /**
     * Remove event from _callbacks array
     *
     * @param string $type
     * @param string $callback
     */
    public static function remove($type, $callback)
    {
        if (!empty(self::$callbacks[$type])) {
            foreach (self::$callbacks[$type] as $i => $found) {
                if ($callback == $found) {
                    unset(self::$callbacks[$type][$i]);
                }
            }
        }
    }

}
