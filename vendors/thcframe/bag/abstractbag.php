<?php

namespace THCFrame\Bag;

use THCFrame\Bag\BagInterface;

/**
 * Description of abstractbag
 *
 * @author Tomy
 */
abstract class AbstractBag implements BagInterface
{

    private $data = [];

    private $dataBagName;

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
                'Undefined property via __get(): ' . $name .
                ' in ' . $trace[0]['file'] .
                ' on line ' . $trace[0]['line'], E_USER_NOTICE);
        return null;
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }

    /**
     *
     * @param type $data
     * @return \THCFrame\Bag\AbstractBag
     */
    public function initialize($data)
    {
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $this->data[$key] = $value;
            }
        }

        return $this;
    }

    /**
     *
     * @param type $key
     * @param type $default
     * @return type
     */
    public function get($key, $default = null)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return $default;
        }
    }

    /**
     *
     * @param type $key
     * @param type $value
     * @return \THCFrame\Bag\AbstractBag
     */
    public function set($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     *
     * @return \THCFrame\Bag\AbstractBag
     */
    public function clear()
    {
        $this->data = [];
        return $this;
    }

    /**
     *
     * @param type $key
     * @return \THCFrame\Bag\AbstractBag
     */
    public function remove($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }

        return $this;
    }

    /**
     *
     * @return type
     */
    public function getName()
    {
        return $this->dataBagName;
    }

    /**
     *
     * @param type $name
     * @return \THCFrame\Bag\AbstractBag
     */
    public function setName($name)
    {
        $this->dataBagName = '_databag_'.$name;
        return $this;
    }

    abstract public function hashKey($key);
}
