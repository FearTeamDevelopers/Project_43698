<?php

namespace Api\Etc;

use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Controller\RestController;

/**
 * Module specific controller class extending framework controller class.
 */
class Controller extends RestController
{

    /**
     * @param type $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        // schedule disconnect from database
        Event::add('framework.controller.destruct.after', function ($name) {
            Registry::get('database')->disconnectAll();
        });
    }

    /**
     * @param type $key
     * @param type $args
     *
     * @return type
     */
    public function lang($key, $args = [])
    {
        return $this->getLang()->_get($key, $args);
    }
}
