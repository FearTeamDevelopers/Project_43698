<?php

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings
 */
class Search_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'Search';

    /**
     * @read
     */
    protected $_observerClass = 'Search_Etc_Observer';
    
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
