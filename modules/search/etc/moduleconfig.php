<?php

namespace Search\Etc;

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings
 */
class ModuleConfig extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'Search';

    /**
     * @read
     */
    protected $_observerClass = 'Search\Etc\ModuleObserver';
    
    /**
     * @read
     * @var array 
     */
    protected $_routes = array(
        array(
            'pattern' => '/dosearch/:page',
            'module' => 'search',
            'controller' => 'search',
            'action' => 'doSearch',
            'args' => ':page'
        ),
        array(
            'pattern' => '/doadsearch/:page',
            'module' => 'search',
            'controller' => 'search',
            'action' => 'doAdSearch',
            'args' => ':page'
        ),
        array(
            'pattern' => '/s/buildindex',
            'module' => 'search',
            'controller' => 'index',
            'action' => 'buildIndex'
        ),
        array(
            'pattern' => '/s/updateindex/:model',
            'module' => 'search',
            'controller' => 'index',
            'action' => 'updateIndex',
            'args' => ':model'
        )
    );

}
