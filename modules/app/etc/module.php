<?php

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings
 */
class App_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'App';

    /**
     * @readwrite
     */
    protected $_checkForRedirects = true;
    
    /**
     * @read
     * @var array 
     */
    protected $_routes = array(
        array(
            'pattern' => '/prohledatbazar',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'search'
        ),
        array(
            'pattern' => '/hledat',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'search'
        ),
        array(
            'pattern' => '/akce',
            'module' => 'app',
            'controller' => 'action',
            'action' => 'index'
        ),
        array(
            'pattern' => '/novinky',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index'
        ),
        array(
            'pattern' => '/reportaze',
            'module' => 'app',
            'controller' => 'report',
            'action' => 'index'
        ),
        array(
            'pattern' => '/bazar',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'index',
        ),
        array(
            'pattern' => '/galerie',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'index',
        ),
        array(
            'pattern' => '/nenalezeno',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'notFound',
        ),
        array(
            'pattern' => '/login',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'login',
        ),
        array(
            'pattern' => '/logout',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'logout',
        ),
        array(
            'pattern' => '/admin',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index',
        ),
        array(
            'pattern' => '/page/:urlkey',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'loadContent',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/bazar/:urlkey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/bazar/:type/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'listByType',
            'args' => array(':type', ':page')
        ),
        array(
            'pattern' => '/galerie/r/:urlkey',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/galerie/p/:page',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'index',
            'args' => ':page'
        ),
        array(
            'pattern' => '/akce/p/:page',
            'module' => 'app',
            'controller' => 'action',
            'action' => 'index',
            'args' => ':page'
        ),
        array(
            'pattern' => '/akce/r/:urlkey',
            'module' => 'app',
            'controller' => 'action',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/reportaze/p/:page',
            'module' => 'app',
            'controller' => 'report',
            'action' => 'index',
            'args' => ':page'
        ),
        array(
            'pattern' => '/reportaze/r/:urlkey',
            'module' => 'app',
            'controller' => 'report',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/novinky/p/:page',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index',
            'args' => ':page'
        ),
        array(
            'pattern' => '/novinky/r/:urlkey',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/bazar/:type/:urlkey/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'listByTypeUrlkey',
            'args' => array(':type', ':urlkey', ':page')
        )
    );

}
