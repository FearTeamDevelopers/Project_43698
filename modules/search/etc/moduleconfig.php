<?php

namespace Search\Etc;

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings.
 */
class ModuleConfig extends Module
{
    /**
     * @read
     */
    protected $moduleName = 'Search';

    /**
     * @read
     */
    protected $observerClass = 'Search\Etc\ModuleObserver';

    /**
     * @read
     *
     * @var array
     */
    protected $routes = [
        [
            'pattern' => '/dosearch/:page',
            'module' => 'search',
            'controller' => 'search',
            'action' => 'dosearch',
            'args' => ':page',
        ],
        [
            'pattern' => '/doadsearch/:page',
            'module' => 'search',
            'controller' => 'search',
            'action' => 'doadsearch',
            'args' => ':page',
        ],
        [
            'pattern' => '/s/buildindex',
            'module' => 'search',
            'controller' => 'index',
            'action' => 'buildindex',
        ],
        [
            'pattern' => '/s/updateindex/:model',
            'module' => 'search',
            'controller' => 'index',
            'action' => 'updateindex',
            'args' => ':model',
        ],
    ];
}
