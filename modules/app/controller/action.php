<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class App_Controller_Action extends Controller
{

    /**
     * 
     * @param type $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $config = Registry::get('configuration');

        $articlesPerPage = $config->actions_per_page;

        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/akce';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/akce/p/' . $page;
        }

        $content = $this->getCache()->get('akce-' . $page);
        
        if ($content !== null) {
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

        if ($actionsPageCount > 1) {
            $prevPage = $page - 1;
            $nextPage = $page + 1;

            if ($nextPage > $actionsPageCount) {
                $nextPage = 0;
            }

            $layoutView
                    ->set('pagedprev', $prevPage)
                    ->set('pagedprevlink', '/akce/p/' . $prevPage)
                    ->set('pagednext', $nextPage)
                    ->set('pagednextlink', '/akce/p/' . $nextPage);
        }

        $view->set('actions', $actions)
                ->set('currentpage', $page)
                ->set('pagecount', $actionsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Akce');
    }

    /**
     * 
     * @param type $urlKey
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $uri = RequestMethods::server('REQUEST_URI');

        $action = App_Model_Action::fetchByUrlKey($urlKey);

        if ($action === null) {
            self::redirect('/nenalezeno');
        }

        $canonical = 'http://' . $this->getServerHost() . '/akce/r/' . $action->getUrlKey();

        $layoutView->set('canonical', $canonical)
                ->set('article', 1)
                ->set('articlecreated', $action->getCreated())
                ->set('articlemodified', $action->getModified())
                ->set('metatitle', $action->getTitle())
                ->set('metaogurl', "http://{$this->getServerHost()}{$uri}")
                ->set('metaogtype', 'article');

        $view->set('action', $action);
    }

}
