<?php

namespace Admin\Etc;

use THCFrame\Module\Module;

/**
 * Class for module specific settings.
 */
class ModuleConfig extends Module
{
    /**
     * @read
     */
    protected $moduleName = 'Admin';

    /**
     * @read
     */
    protected $observerClass = 'Admin\Etc\ModuleObserver';

    /**
     * @read
     *
     * @var array
     */
    protected $routes = [
        [
            'pattern' => '/admin/login',
            'module' => 'admin',
            'controller' => 'user',
            'action' => 'login',
        ],
        [
            'pattern' => '/admin/logout',
            'module' => 'admin',
            'controller' => 'user',
            'action' => 'logout',
        ],
        [
            'pattern' => '/admin/email/loadtemplate/:id/:lang',
            'module' => 'admin',
            'controller' => 'email',
            'action' => 'loadtemplate',
            'args' => [':id', ':lang'],
        ],
    ];
}
