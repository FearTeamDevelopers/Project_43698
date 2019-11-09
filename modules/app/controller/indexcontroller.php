<?php

namespace App\Controller;

use App\Etc\Controller;
use App\Model\ActionModel;
use App\Model\NewsModel;
use App\Model\PageContentModel;
use App\Model\PartnerModel;
use App\Model\ReportModel;
use THCFrame\Model\Model;
use THCFrame\Request\Exception\Response;
use THCFrame\Request\Request;
use THCFrame\Request\RequestMethods;
use THCFrame\View\Exception\Data;
use THCFrame\View\View;

/**
 *
 */
class IndexController extends Controller
{

    /**
     * Landing page.
     */
    public function index()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $canonical = $this->getServerHost();

        $news = $this->getCache()->get('index-news');

        if (null === $news) {
            $news = NewsModel::fetchActiveWithLimit(4);
            $this->getCache()->set('index-news', $news);
        }

        $actions = $this->getCache()->get('index-actions');

        if (null === $actions) {
            $actions = ActionModel::fetchActiveWithLimit(9);
            $this->getCache()->set('index-actions', $actions);
        }

        $reports = $this->getCache()->get('index-reports');

        if (null === $reports) {
            $reports = ReportModel::fetchActiveWithLimit(7);
            $this->getCache()->set('index-reports', $reports);
        }

        $partners = $this->getCache()->get('index-partners');

        if (null === $partners) {
            $partners = PartnerModel::all(
                ['active = ?' => true], ['*'], ['rank' => 'desc', 'created' => 'desc']
            );
            $this->getCache()->set('index-partners', $partners);
        }

        $view->set('news', $news)
            ->set('actions', $actions)
            ->set('partners', $partners)
            ->set('reports', $reports);

        $layoutView->set('includecarousel', 1)
            ->set(View::META_CANONICAL, $canonical);
    }

    /**
     * Default method for content loading.
     * @param $urlKey
     * @throws Data
     */
    public function loadContent($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('content_' . $urlKey);

        if (null === $content) {
            $content = PageContentModel::fetchByUrlKey($urlKey);

            if ($content === null) {
                self::redirect('/nenalezeno');
            }

            $this->getCache()->set('content_' . $urlKey, $content);
        }

        $this->_checkMetaData($layoutView, $content);

        $view->set('content', $content);
    }

    /**
     * Check if are set specific metadata or leave their default values.
     * @param $layoutView
     * @param Model $object
     */
    private function _checkMetaData($layoutView, Model $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaTitle() != '') {
            $layoutView->set(View::META_TITLE, $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set(View::META_DESCRIPTION, $object->getMetaDescription());
        }

        $canonical = "{$this->getServerHost()}{$uri}";

        $layoutView->set(View::META_CANONICAL, $canonical)
            ->set('metaogurl', "{$this->getServerHost()}{$uri}")
            ->set('metaogtype', 'website');
    }

    /**
     * Custom 404 page.
     */
    public function notFound()
    {
        $canonical = $this->getServerHost() . '/nenalezeno';

        $this->getLayoutView()
            ->set(View::META_CANONICAL, $canonical)
            ->set(View::META_TITLE, 'Hastrman - Stránka nenalezena');
    }

    /**
     * Search in application, exclude advertisements.
     * @param int $page
     * @throws Response
     * @throws Data
     */
    public function search($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $articlesPerPage = $this->getConfig()->search_results_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        $searchString = RequestMethods::get('str');

        if (empty($searchString)) {
            $view->warningMessage('Musíte zadate text, který chcete vyhledat');
            self::redirect('/');
        }

        $canonical = $this->getServerHost() . '/hledat';
        $requestUrl = $this->getServerHost() . '/dosearch/' . $page;
        $parameters = ['str' => $searchString];

        $request = new Request();
        $response = $request->request('post', $requestUrl, $parameters);
        $searchResult = json_decode($response, true);

        if (null !== $searchResult) {
            $articleCount = array_shift($searchResult);
            $searchPageCount = ceil($articleCount / $articlesPerPage);
            $this->pagerMetaLinks($searchPageCount, $page, '/hledat/p/');

            $view->set('results', $searchResult)
                ->set('currentpage', $page)
                ->set('pagecount', $searchPageCount)
                ->set('pagerpathprefix', '/hledat')
                ->set('pagerpathpostfix', '?' . http_build_query($parameters));
        }

        $layoutView->set(View::META_CANONICAL, $canonical)
            ->set(View::META_TITLE, 'Hastrman - Hledat');
    }

    /**
     *
     */
    public function privacyPolicy()
    {
        $canonical = $this->getServerHost() . '/ochrana-soukromi';

        $this->getLayoutView()
            ->set(View::META_CANONICAL, $canonical)
            ->set(View::META_TITLE, 'Hastrman - Ochrana soukromí');
    }

    /**
     *
     */
    public function privacysettings()
    {
        $canonical = $this->getServerHost() . '/nastaveni-soukromi';

        $this->getLayoutView()
            ->set(View::META_CANONICAL, $canonical)
            ->set(View::META_TITLE, 'Hastrman - Nastavení soukromí');
    }

    /**
     *
     */
    public function cookiePolicy()
    {
        $canonical = $this->getServerHost() . '/zasady-cookies';

        $this->getLayoutView()
            ->set(View::META_CANONICAL, $canonical)
            ->set(View::META_TITLE, 'Hastrman - Zásady cookies');
    }
}
