<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class App_Controller_Action extends Controller
{

    /**
     * Check if are set specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, App_Model_Action $object)
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
            $actions = App_Model_Action::fetchOldWithLimit($articlesPerPage, $page);

            $this->getCache()->set('akce-' . $page, $actions);
        }

        $actionCount = App_Model_Action::count(
                        array('active = ?' => true,
                            'approved = ?' => 1)
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

        $action = App_Model_Action::fetchByUrlKey($urlKey);

        if ($action === null) {
            self::redirect('/nenalezeno');
        }

        $this->_checkMetaData($layoutView, $action);
        $view->set('action', $action);
    }

}
