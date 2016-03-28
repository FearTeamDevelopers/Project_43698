<?php

namespace Api\Etc;

use THCFrame\Module\Module;

/**
 * Class for module specific settings.
 */
class ModuleConfig extends Module
{
    /**
     * @read
     */
    protected $_moduleName = 'Api';

    /**
     * @read
     */
    protected $_observerClass = 'Api\Etc\ModuleObserver';

    /**
     * @read
     *
     * @var array
     */
    protected $_routes = array(
        array(
            'pattern' => '/api/v1/login',
            'module' => 'api',
            'controller' => 'user',
            'action' => 'login',
        ),
        array(
            'pattern' => '/api/v1/log-book-sync',
            'module' => 'api',
            'controller' => 'index',
            'action' => 'logbooksync',
        ),
    );
}
