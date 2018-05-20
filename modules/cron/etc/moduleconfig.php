<?php

namespace Cron\Etc;

use THCFrame\Module\Module;

/**
 * Class for module specific settings.
 */
class ModuleConfig extends Module
{
    /**
     * @read
     */
    protected $moduleName = 'Cron';

    /**
     * @read
     */
    protected $observerClass = 'Cron\Etc\ModuleObserver';

    /**
     * @read
     *
     * @var array
     */
    protected $routes = [
        [
            'pattern' => '/c/generatesitemap',
            'module' => 'cron',
            'controller' => 'index',
            'action' => 'generatesitemap',
        ],
        [
            'pattern' => '/c/dbbackup',
            'module' => 'cron',
            'controller' => 'backup',
            'action' => 'dailydatabasebackup',
        ],
        [
            'pattern' => '/c/monthdbbackup',
            'module' => 'cron',
            'controller' => 'backup',
            'action' => 'monthlydatabasebackup',
        ],
        [
            'pattern' => '/c/clonedb',
            'module' => 'cron',
            'controller' => 'backup',
            'action' => 'databaseprodtotest',
        ],
        [
            'pattern' => '/c/systemcheck',
            'module' => 'cron',
            'controller' => 'index',
            'action' => 'systemcheck',
        ],
        [
            'pattern' => '/c/filehashscan',
            'module' => 'cron',
            'controller' => 'index',
            'action' => 'filehashscan',
        ],
        [
            'pattern' => '/c/adexpirationcheck',
            'module' => 'cron',
            'controller' => 'advertisement',
            'action' => 'checkadexpirations',
        ],
        [
            'pattern' => '/c/archivateactions',
            'module' => 'cron',
            'controller' => 'archive',
            'action' => 'archivateactions',
        ],
        [
            'pattern' => '/c/archivatenews',
            'module' => 'cron',
            'controller' => 'archive',
            'action' => 'archivatenews',
        ],
        [
            'pattern' => '/c/archivatereports',
            'module' => 'cron',
            'controller' => 'archive',
            'action' => 'archivatereports',
        ],
    ];
}
