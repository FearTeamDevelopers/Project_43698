<?php

namespace THCFrame\Bag;

use THCFrame\Bag\AbstractBag;

/**
 * Description of databag
 *
 * @author Tomy
 */
class DataBag extends AbstractBag
{

    public function __construct(array $data = array())
    {
        $this->initialize($data);
    }

    public function hashKey($key)
    {
        return $key;
    }

}
