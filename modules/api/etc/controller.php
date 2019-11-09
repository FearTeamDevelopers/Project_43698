<?php

namespace Api\Etc;

use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Controller\RestController;

/**
 * Module specific controller class extending framework controller class.
 */
class Controller extends RestController
{

    /**
     * @param array $options
     * @throws \THCFrame\Controller\Exception\Header
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        // schedule disconnect from database
        Event::add('framework.controller.destruct.after', function () {
            Registry::get('database')->disconnectAll();
        });
    }

    /**
     * @param $key
     * @param array $args
     * @return mixed|string
     */
    public function lang($key, $args = [])
    {
        return $this->getLang()->_get($key, $args);
    }
}
