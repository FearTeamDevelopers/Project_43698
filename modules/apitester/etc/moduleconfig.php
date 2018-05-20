<?php

namespace Apitester\Etc;

use THCFrame\Module\Module;

/**
 * Class for module specific settings.
 */
class ModuleConfig extends Module
{
    /**
     * @read
     */
    protected $moduleName = 'Apitester';

    /**
     * @read
     */
    protected $observerClass = 'Apitester\Etc\ModuleObserver';

    /**
     * @read
     *
     * @var array
     */
    protected $routes = [
//        array(
//            'pattern' => '/api/v1/login',
//            'module' => 'api',
//            'controller' => 'user',
//            'action' => 'login',
//        ),
//        array(
//            'pattern' => '/api/v1/log-book-sync',
//            'module' => 'api',
//            'controller' => 'index',
//            'action' => 'logbooksync',
//        ),
    ];
}
