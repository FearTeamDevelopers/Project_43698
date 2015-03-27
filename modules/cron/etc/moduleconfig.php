<?php

namespace Cron\Etc;

use THCFrame\Module\Module;

/**
 * Class for module specific settings
 */
class ModuleConfig extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'Cron';

    /**
     * @read
     */
    protected $_observerClass = 'Cron\Etc\ModuleObserver';

    /**
     * @read
     * @var array 
     */
    protected $_routes = array(
        array(
            'pattern' => '/c/generatesitemap',
            'module' => 'cron',
            'controller' => 'index',
            'action' => 'crongeneratesitemap',
        ),
        array(
            'pattern' => '/c/dbbackup',
            'module' => 'cron',
            'controller' => 'index',
            'action' => 'crondatabasebackup',
        ),
        array(
            'pattern' => '/c/clonedb',
            'module' => 'cron',
            'controller' => 'index',
            'action' => 'crondatabaseprodtotest',
        )
    );

}
