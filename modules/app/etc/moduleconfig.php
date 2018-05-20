<?php
namespace App\Etc;

use THCFrame\Module\Module as Module;

/**
 * Class for module specific settings.
 */
class ModuleConfig extends Module
{

    /**
     * @read
     */
    protected $moduleName = 'App';

    /**
     * @readwrite
     */
    protected $checkForRedirects = true;

    /**
     * @read
     */
    protected $observerClass = 'App\Etc\ModuleObserver';

    /**
     * @read
     *
     * @var array
     */
    protected $routes = [
        [
            'pattern' => '/hledat',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'search',
        ],
        [
            'pattern' => '/admin',
            'module' => 'admin',
            'controller' => 'index',
            'action' => 'index',
        ],
        [
            'pattern' => '/bazar/hledat',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'search',
        ],
        [
            'pattern' => '/bazar/filtr',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'filter',
        ],
        [
            'pattern' => '/page/:urlkey',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'loadcontent',
            'args' => ':urlkey',
        ],
        [
            'pattern' => '/aktivovatucet/:key',
            'module' => 'app',
            'controller' => 'user',
            'action' => 'activateaccount',
            'args' => ':key',
        ],
        [
            'pattern' => '/bazar/smazat/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'ajaxdelete',
            'args' => ':uniquekey',
        ],
        [
            'pattern' => '/bazar/odstranit/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'delete',
            'args' => ':uniquekey',
        ],
        [
            'pattern' => '/bazar/upravit/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'edit',
            'args' => ':uniquekey',
        ],
        [
            'pattern' => '/bazar/smazatfoto/:imageid',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'deleteadimage',
            'args' => ':imageid',
        ],
        [
            'pattern' => '/bazar/prodlouzit/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'extendadexpiration',
            'args' => ':uniquekey',
        ],
        [
            'pattern' => '/bazar/prodano/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'setstatetosold',
            'args' => ':uniquekey',
        ],
        [
            'pattern' => '/bazar/prodlouzit/:uniquekey/:token',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'extendadexpirationfromemail',
            'args' => [':uniquekey', ':token'],
        ],
        [
            'pattern' => '/bazar/nastavitfoto/:adid/:imageid',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'setnewmainphoto',
            'args' => [':adid', ':imageid'],
        ],
        [
            'pattern' => '/bazar/p/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'index',
            'args' => ':page',
        ],
        [
            'pattern' => '/bazar/r/:uniquekey',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'detail',
            'args' => ':uniquekey',
        ],
        [
            'pattern' => '/hledat/p/:page',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'search',
            'args' => ':page',
        ],
        [
            'pattern' => '/galerie/r/:urlkey',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'detail',
            'args' => ':urlkey',
        ],
        [
            'pattern' => '/galerieslideshow/r/:urlkey',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'slideshow',
            'args' => ':urlkey',
        ],
        [
            'pattern' => '/galerie/p/:page',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'index',
            'args' => ':page',
        ],
        [
            'pattern' => '/akce/p/:page',
            'module' => 'app',
            'controller' => 'action',
            'action' => 'index',
            'args' => ':page',
        ],
        [
            'pattern' => '/akce/r/:urlkey',
            'module' => 'app',
            'controller' => 'action',
            'action' => 'detail',
            'args' => ':urlkey',
        ],
        [
            'pattern' => '/reportaze/p/:page',
            'module' => 'app',
            'controller' => 'report',
            'action' => 'index',
            'args' => ':page',
        ],
        [
            'pattern' => '/reportaze/r/:urlkey',
            'module' => 'app',
            'controller' => 'report',
            'action' => 'detail',
            'args' => ':urlkey',
        ],
        [
            'pattern' => '/archiv-reportazi/p/:page',
            'module' => 'app',
            'controller' => 'report',
            'action' => 'archive',
            'args' => ':page',
        ],
        [
            'pattern' => '/novinky/p/:page',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'index',
            'args' => ':page',
        ],
        [
            'pattern' => '/novinky/r/:urlkey',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'detail',
            'args' => ':urlkey',
        ],
        [
            'pattern' => '/archiv-novinek/p/:page',
            'module' => 'app',
            'controller' => 'news',
            'action' => 'archive',
            'args' => ':page',
        ],
        [
            'pattern' => '/bazar/filtr/p/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'filter',
            'args' => ':page',
        ],
        [
            'pattern' => '/bazar/hledat/p/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'search',
            'args' => ':page',
        ],
        [
            'pattern' => '/smazat-komentar/:id',
            'module' => 'app',
            'controller' => 'comment',
            'action' => 'delete',
            'args' => ':id',
        ],
        [
            'pattern' => '/bazar/moje-inzeraty/p/:page',
            'module' => 'app',
            'controller' => 'advertisement',
            'action' => 'listbyuser',
            'args' => ':page',
        ],
        [
            'pattern' => '/galerie/:urlkey/p/:page',
            'module' => 'app',
            'controller' => 'gallery',
            'action' => 'detail',
            'args' => [':urlkey', ':page'],
        ],
        [
            'pattern' => '/akce/ucast/:id/:type',
            'module' => 'app',
            'controller' => 'action',
            'action' => 'attendance',
            'args' => [':id', ':type'],
        ],
        [
            'pattern' => '/akce/archiv/:year/:?page',
            'module' => 'app',
            'controller' => 'action',
            'action' => 'archive',
            'args' => [':year', ':?page'],
        ],
    ];

}
