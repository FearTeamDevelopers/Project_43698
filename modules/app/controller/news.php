<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class App_Controller_News extends Controller
{

    /**
     * Check if are set specific metadata or leave their default values
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
     * Get list of news
     * 
     * @param int $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->news_per_page;

        if($page <= 0){
            $page = 1;
        }
        
        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/novinky';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/novinky/p/' . $page;
        }
        
        $content = $this->getCache()->get('news-' . $page);
        
        if (null !== $content) {
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

        $this->_pagerMetaLinks($newsPageCount, $page, '/novinky/p/');

        $view->set('news', $news)
                ->set('currentpage', $page)
                ->set('path', '/novinky')
                ->set('pagecount', $newsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Novinky');
    }

    /**
     * Show news detail
     * 
     * @param string $urlKey
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
