<?php
namespace Queue\Etc;

use THCFrame\Module\Module;

/**
 * Class for module specific settings.
 */
class ModuleConfig extends Module
{

    /**
     * @read
     */
    protected $moduleName = 'Queue';

    /**
     * @read
     */
    protected $observerClass = 'Queue\Etc\ModuleObserver';

    /**
     * @read
     *
     * @var array
     */
    protected $routes = [];

}
