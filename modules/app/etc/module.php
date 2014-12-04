<?php

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings
 *
 * 
 */
class App_Etc_Module extends Module
{

    /**
     * @read
     */
    protected $_moduleName = 'App';

    /**
     * @read
     * @var type 
     */
    protected $_routes = array(
        array(
            'pattern' => '/bazar/:type/:urlkey/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'listByTypeUrlkey',
            'args' => array(':type', ':urlkey', ':page')
        ),
        array(
            'pattern' => '/bazar/:type/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'listByType',
            'args' => array(':type',':page')
        ),
        array(
            'pattern' => '/galerie/r/:urlkey',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'detail',
            'args' => ':urlkey'
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
            'pattern' => '/bazar/:urlkey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'detail',
            'args' => ':urlkey'
        ),
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
    );

    /**
     * @read
     * @var type 
     */
    protected $_redirects = array(
        array(
            'from' => '/odkazy',
            'to' => '/app/index/loadcontent/odkazy'
        ),
        array(
            'from' => '/sluzby/plneni-lahvi',
            'to' => '/app/index/loadcontent/plneni-lahvi'
        ),
        array(
            'from' => '/sluzby/prodej-nove-vystroje',
            'to' => '/app/index/loadcontent/prodej-nove-vystroje'
        ),
        array(
            'from' => '/sluzby/prace-pod-vodou',
            'to' => '/app/index/loadcontent/prace-pod-vodou'
        ),
        array(
            'from' => '/sluzby/pujcovna-potapecske-vystroje',
            'to' => '/app/index/loadcontent/pujcovna-potapecske-vystroje'
        ),
        array(
            'from' => '/sluzby/servis-potapecske-techniky',
            'to' => '/app/index/loadcontent/servis-potapecske-techniky'
        ),
        array(
            'from' => '/bazen',
            'to' => '/app/index/loadcontent/bazen'
        ),
        array(
            'from' => '/technika',
            'to' => '/app/index/loadcontent/technika'
        ),
        array(
            'from' => '/pojisteni',
            'to' => '/app/index/loadcontent/pojisteni'
        ),
        array(
            'from' => '/kurzy',
            'to' => '/app/index/loadcontent/kurzy'
        ),
        array(
            'from' => '/kurzy/cmas',
            'to' => '/app/index/loadcontent/kurzy-cmas'
        ),
        array(
            'from' => '/kurzy/tdi',
            'to' => '/app/index/loadcontent/kurzy-tdi'
        ),
        array(
            'from' => '/kurzy/sdi',
            'to' => '/app/index/loadcontent/kurzy-sdi'
        ),
        array(
            'from' => '/kurzy/udi',
            'to' => '/app/index/loadcontent/kurzy-udi'
        ),
        array(
            'from' => '/bazar/moje-inzeraty',
            'to' => '/app/advertisement/listbyuser/'
        ),
    );
}
