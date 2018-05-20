<?php

namespace Api\Etc;

use THCFrame\Module\Module;
use THCFrame\Router\Route;

/**
 * Class for module specific settings.
 */
class ModuleConfig extends Module
{
    /**
     * @read
     */
    protected $moduleName = 'Api';

    /**
     * @read
     */
    protected $observerClass = 'Api\Etc\ModuleObserver';

    /**
     * @read
     *
     * @var array
     */
    protected $routes = [
        [
            'pattern' => '/api/v1/login',
            'module' => 'api',
            'controller' => 'user',
            'action' => 'login',
            'method' => Route::HTTP_POST
        ],
        [
            'pattern' => '/api/v1/log-book-sync',
            'module' => 'api',
            'controller' => 'index',
            'action' => 'logbooksync',
            'method' => Route::HTTP_POST
        ],
    ];
}
