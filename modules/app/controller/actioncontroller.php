<?php

namespace App\Controller;

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class ActionController extends Controller
{

    /**
     * Check if are set specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, \App\Model\ActionModel $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        $canonical = 'http://' . $this->getServerHost() . '/akce/r/' . $object->getUrlKey();

        $layoutView->set('canonical', $canonical)
                ->set('article', 1)
                ->set('articlecreated', $object->getCreated())
                ->set('articlemodified', $object->getModified())
                ->set('metaogurl', "http://{$this->getServerHost()}{$uri}")
                ->set('metaogtype', 'article');
    }
    
    /**
     * Get list of actions
     * 
     * @param int $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->actions_per_page;

        if($page <= 0){
            $page = 1;
        }
        
        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/akce';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/akce/p/' . $page;
        }

        $content = $this->getCache()->get('akce-' . $page);
        
        if (null !== $content) {
            $actions = $content;
        } else {
            $actions = \App\Model\ActionModel::fetchActiveWithLimit($articlesPerPage, $page);

            $this->getCache()->set('akce-' . $page, $actions);
        }

        $actionCount = \App\Model\ActionModel::count(
                        array('active = ?' => true,
                            'approved = ?' => 1,
                            'archive = ?' => false,
                            'startDate >= ?' => date('Y-m-d', time()))
        );

        $actionsPageCount = ceil($actionCount / $articlesPerPage);

        $this->_pagerMetaLinks($actionsPageCount, $page, '/akce/p/');

        $view->set('actions', $actions)
                ->set('pagerpathprefix', '/akce')
                ->set('currentpage', $page)
                ->set('pagecount', $actionsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Akce');
    }

    /**
     * Show action detail
     * 
     * @param string $urlKey
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $action = \App\Model\ActionModel::fetchByUrlKey($urlKey);

        if ($action === null) {
            self::redirect('/nenalezeno');
        }

        $this->_checkMetaData($layoutView, $action);
        $view->set('action', $action);
    }
    
    /**
     * 
     * @param type $page
     */
    public function archive($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->actions_per_page;

        if($page <= 0){
            $page = 1;
        }
        
        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/archivakci';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/archivakci/p/' . $page;
        }
        
        $actions = \App\Model\ActionModel::fetchOldWithLimit($articlesPerPage, $page);

        $actionCount = \App\Model\ActionModel::count(
                        array('active = ?' => true,
                            'approved = ?' => 1,
                            'startDate <= ?' => date('Y-m-d', time()))
        );

        $actionsPageCount = ceil($actionCount / $articlesPerPage);

        $this->_pagerMetaLinks($actionsPageCount, $page, '/archivakci/p/');

        $view->set('actions', $actions)
                ->set('pagerpathprefix', '/archivakci')
                ->set('currentpage', $page)
                ->set('pagecount', $actionsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Akce - Archiv');
    }

    /**
     * Preview of action created in administration but not saved into db
     * 
     * @before _secured, _participant
     */
    public function preview()
    {
        $view = $this->getActionView();
        $session = Registry::get('session');
        
        $action = $session->get('actionPreview');

        if(null === $action){
            $this->_willRenderActionView = false;
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/action/');
        }
        
        $act = RequestMethods::get('action');
        
        $view->set('action', $action)
            ->set('act', $act);
    }
}
