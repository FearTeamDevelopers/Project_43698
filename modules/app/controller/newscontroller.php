<?php

namespace App\Controller;

use App\Etc\Controller;
use App\Model\NewsModel;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\View\Exception\Data;
use THCFrame\View\View;

/**
 *
 */
class NewsController extends Controller
{

    /**
     * Get list of news.
     *
     * @param int $page
     * @throws Connector
     * @throws Implementation
     * @throws Data
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->news_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/novinky';
        } else {
            $canonical = $this->getServerHost() . '/novinky/p/' . $page;
        }

        $content = $this->getCache()->get('news-' . $page);

        if (null !== $content) {
            $news = $content;
        } else {
            $news = NewsModel::fetchActiveWithLimit($articlesPerPage, $page);

            $this->getCache()->set('news-' . $page, $news);
        }

        $newsCount = NewsModel::count(
            [
                'active = ?' => true,
                'archive = ?' => false,
                'approved = ?' => 1,
            ]
        );
        $newsPageCount = ceil($newsCount / $articlesPerPage);

        $this->pagerMetaLinks($newsPageCount, $page, '/novinky/p/');

        $view->set('news', $news)
            ->set('currentpage', $page)
            ->set('pagerpathprefix', '/novinky')
            ->set('pagecount', $newsPageCount);

        $layoutView->set(View::META_CANONICAL, $canonical)
            ->set(View::META_TITLE, 'Hastrman - Novinky');
    }

    /**
     * Get list of archivated news.
     *
     * @param int $page
     * @throws Connector
     * @throws Implementation
     * @throws Data
     */
    public function archive($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->news_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/archiv-novinek';
        } else {
            $canonical = $this->getServerHost() . '/archiv-novinek/p/' . $page;
        }

        $content = $this->getCache()->get('news-arch-' . $page);

        if (null !== $content) {
            $news = $content;
        } else {
            $news = NewsModel::fetchArchivatedWithLimit($articlesPerPage, $page);

            $this->getCache()->set('news-arch-' . $page, $news);
        }

        $newsCount = NewsModel::count(
            [
                'active = ?' => true,
                'archive = ?' => true,
                'approved = ?' => 1,
            ]
        );
        $newsPageCount = ceil($newsCount / $articlesPerPage);

        $this->pagerMetaLinks($newsPageCount, $page, '/archiv-novinek/p/');

        $view->set('news', $news)
            ->set('currentpage', $page)
            ->set('pagerpathprefix', '/archiv-novinek')
            ->set('pagecount', $newsPageCount);

        $layoutView->set(View::META_CANONICAL, $canonical)
            ->set(View::META_TITLE, 'Hastrman - Novinky - Archiv');
    }

    /**
     * Show news detail.
     *
     * @param string $urlKey
     * @throws Data
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $news = NewsModel::fetchByUrlKey($urlKey);

        if ($news === null) {
            self::redirect('/nenalezeno');
        }

        $this->_checkMetaData($layoutView, $news);
        $view->set('news', $news);
    }

    /**
     * Check if are set specific metadata or leave their default values.
     * @param $layoutView
     * @param NewsModel $object
     */
    private function _checkMetaData($layoutView, NewsModel $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaTitle() != '') {
            $layoutView->set(View::META_TITLE, 'Novinky - ' . $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set(View::META_DESCRIPTION, $object->getMetaDescription());
        }

        $canonical = $this->getServerHost() . '/novinky/r/' . $object->getUrlKey();

        $layoutView->set(View::META_CANONICAL, $canonical)
            ->set('article', 1)
            ->set('articlecreated', $object->getCreated())
            ->set('articlemodified', $object->getModified())
            ->set('metaogurl', "{$this->getServerHost()}{$uri}")
            ->set('metaogtype', 'article');
    }

    /**
     * Preview of news created in administration but not saved into db.
     *
     * @before _secured, _participant
     */
    public function preview()
    {
        $view = $this->getActionView();
        $session = Registry::get('session');

        $news = $session->get('newsPreview');

        if (null === $news) {
            $this->willRenderActionView = false;
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/news/');
        }

        $act = RequestMethods::get('action');

        $view->set('news', $news)
            ->set('act', $act);
    }

}
