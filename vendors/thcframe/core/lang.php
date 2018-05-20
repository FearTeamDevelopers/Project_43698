<?php

namespace THCFrame\Core;

use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry as Registry;

/**
 * Class controlling translates
 */
class Lang
{

    private $customTranslates = [];
    private $defaultMessage;
    public static $instance = null;

    public function __construct()
    {
        Event::fire('framework.lang.initialize.before', []);

        $defaultLang = Registry::get('configuration')->system->lang;

        if (file_exists(APP_PATH . '/lang/' . $defaultLang . '.php')) {
            $custom = include (APP_PATH . '/lang/' . $defaultLang . '.php');
        }

        if (!is_array($custom)) {
            throw new Exception\Lang('Lang file content is not array');
        }

        $prepared = [];
        foreach ($custom as $key => $value) {
            $key = trim(StringMethods::removeDiacriticalMarks($key));
            $key = strtoupper(str_replace(' ', '_', $key));
            $prepared[$key] = $value;
        }

        unset($custom);

        if (isset($prepared['defaultMessage'])) {
            $this->defaultMessage = $prepared['defaultMessage'];
            unset($prepared['defaultMessage']);
        } else {
            $this->defaultMessage = 'Translate not found';
        }

        $this->customTranslates = $prepared;

        Event::fire('framework.lang.initialize.after', [$defaultLang]);
    }

    /**
     * @return null|Lang
     * @throws Exception\Lang
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $key
     * @param array $args
     * @return mixed|string
     * @throws Exception\Lang
     */
    public static function get($key, $args = [])
    {
        $lang = self::getInstance();
        return $lang->_get($key, $args);
    }

    /**
     * @param string $key
     * @param array $args
     * @return mixed|string
     */
    public function _get($key, $args = [])
    {
        $key = trim(StringMethods::removeDiacriticalMarks($key));
        $key = strtoupper(str_replace(' ', '_', $key));

        if (isset($this->customTranslates[$key])) {
            if (!empty($args) && is_array($args)) {
                if (strpos($this->customTranslates[$key], '%s') !== false) {
                    return vsprintf($this->customTranslates[$key], $args);
                }
            }

            return $this->customTranslates[$key];
        }

        return $this->defaultMessage;
    }

}
