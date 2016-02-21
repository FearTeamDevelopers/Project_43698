<?php

namespace THCFrame\Session;

use THCFrame\Core\Base;
use THCFrame\Session\Exception;
use THCFrame\Bag\BagInterface;

/**
 * Factory allows many different kinds of configuration driver classes to be used,
 * we need a way to share code across all driver classes.
 */
abstract class Driver extends Base implements BagInterface
{

    protected $dataBagName;

    /**
     *
     * @return \THCFrame\Session\Driver
     */
    public function initialize()
    {
        return $this;
    }

    /**
     *
     * @param type $method
     * @return \THCFrame\Session\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    public abstract function get($key, $default = null);

    public abstract function set($key, $value);

    public abstract function remove($key);

    public abstract function clear();

    public abstract function hashKey($key);

    public abstract function setName($name);

    public abstract function getName();
}
