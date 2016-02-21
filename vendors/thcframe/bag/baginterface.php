<?php

namespace THCFrame\Bag;

/**
 * Description of BagInterface
 *
 * @author Tomy
 */
interface BagInterface
{

    public function setName($name);

    public function getName();

    public function get($key, $default = null);

    public function set($key, $value);

    public function remove($key);

    public function clear();

    public function hashKey($key);
}
