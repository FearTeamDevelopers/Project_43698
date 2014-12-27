<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * 
 */
class App_Controller_News extends Controller
{

    /**
     * Check if are sets specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, App_Model_News $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        $canonical = 'http://' . $this->getServerHost() . '/novinky/r/' . $object->getUrlKey();

        $layoutView->set('canonical', $canonical)
                ->set('article', 1)
                ->set('articlecreated', $object->getCreated())
                ->set('articlemodified', $object->getModified())
                ->set('metaogurl', "http://{$this->getServerHost()}{$uri}")
                ->set('metaogtype', 'article');
    }

    /**
     * 
     * @param type $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $config = Registry::get('configuration');

        $articlesPerPage = $config->news_per_page;

        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/novinky';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/novinky/p/' . $page;
        }
        
        $content = $this->getCache()->get('news-' . $page);
        
        if ($content !== null) {
            $news = $content;
        } else {
            $news = App_Model_News::fetchOldWithLimit($articlesPerPage, $page);

            $this->getCache()->set('news-' . $page, $news);
        }

        $newsCount = App_Model_Action::count(
                        array('active = ?' => true,
                            'approved = ?' => 1)
        );
        $newsPageCount = ceil($newsCount / $articlesPerPage);

        if ($newsPageCount > 1) {
            $prevPage = $page - 1;
            $nextPage = $page + 1;

            if ($nextPage > $newsPageCount) {
                $nextPage = 0;
            }

            $layoutView
                    ->set('pagedprev', $prevPage)
                    ->set('pagedprevlink', '/novinky/p/' . $prevPage)
                    ->set('pagednext', $nextPage)
                    ->set('pagednextlink', '/novinky/p/' . $nextPage);
        }

        $view->set('news', $news)
                ->set('currentpage',$page)
                ->set('pagecount', $newsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Novinky');
    }

    /**
     * 
     * @param type $urlKey
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        
        $news = App_Model_News::fetchByUrlKey($urlKey);
        
        if($news === null){
            self::redirect('/nenalezeno');
        }
        
        $this->_checkMetaData($layoutView, $news);
        $view->set('news', $news);
    }
}
