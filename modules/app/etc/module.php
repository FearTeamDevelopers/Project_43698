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
            'action' => 'index'
        ),
        array(
            'pattern' => '/galerie',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'index'
        ),
        array(
            'pattern' => '/nenalezeno',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'notFound'
        ),
        array(
            'pattern' => '/login',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'login'
        ),
        array(
            'pattern' => '/muj-profil',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'profile'
        ),
        array(
            'pattern' => '/registrace',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'registration'
        ),
        array(
            'pattern' => '/logout',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'logout'
        ),
        array(
            'pattern' => '/admin',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index'
        ),
        array(
            'pattern' => '/page/:urlkey',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'loadContent',
            'args' => ':urlkey'
        ),
        array(
            'pattern' => '/aktivovatucet/:key',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'activateAccount',
            'args' => ':key'
        ),
        array(
            'pattern' => '/bazar/nenalezeno',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'notFound'
        ),
        array(
            'pattern' => '/bazar/pridat',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'add'
        ),
        array(
            'pattern' => '/bazar/moje-inzeraty',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'listByUser'
        ),
        array(
            'pattern' => '/bazar/filtr',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'filter'
        ),
        array(
            'pattern' => '/bazar/smazat/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'delete',
            'args' => ':uniquekey'
        ),
        array(
            'pattern' => '/bazar/upravit/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'edit',
            'args' => ':uniquekey'
        ),
        array(
            'pattern' => '/bazar/prodlouzit/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'sendAvailabilityExtendRequest',
            'args' => ':uniquekey'
        ),
        array(
            'pattern' => '/bazar/p/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'index',
            'args' => ':page'
        ),
        array(
            'pattern' => '/bazar/pridat',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'add',
        ),
        array(
            'pattern' => '/bazar/moje-inzeraty',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'listByUser',
        ),
        array(
            'pattern' => '/bazar/r/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'detail',
            'args' => ':uniquekey'
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
            'pattern' => '/bazar/filtr/p/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'filter',
            'args' => ':page'
        ),
        array(
            'pattern' => '/bazar/moje-inzeraty/p/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'listByUser',
            'args' => ':page'
        )
    );

}
