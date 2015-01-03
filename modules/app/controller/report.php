<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class App_Controller_Report extends Controller
{

    /**
     * Check if are set specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, App_Model_Report $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaImage() != '') {
            $layoutView->set('metaogimage', "http://{$this->getServerHost()}{$object->getMetaImage()}");
        }

        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        $canonical = 'http://' . $this->getServerHost() . '/reportaze/r/' . $object->getUrlKey();

        $layoutView->set('canonical', $canonical)
                ->set('article', 1)
                ->set('articlecreated', $object->getCreated())
                ->set('articlemodified', $object->getModified())
                ->set('metaogurl', "http://{$this->getServerHost()}{$uri}")
                ->set('metaogtype', 'article');
    }

    /**
     * Get list of reports
     * 
     * @param int $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->reports_per_page;

        if($page <= 0){
            $page = 1;
        }
        
        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/reportaze';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/reportaze/p/' . $page;
        }
        
        $content = $this->getCache()->get('report-' . $page);

        if (null !== $content) {
            $reports = $content;
        } else {
            $reports = App_Model_Report::fetchOldWithLimit($articlesPerPage, $page);

            $this->getCache()->set('report-' . $page, $reports);
        }

        $reportCount = App_Model_Report::count(
                        array('active = ?' => true,
                            'approved = ?' => 1)
        );
        $reportsPageCount = ceil($reportCount / $articlesPerPage);

        $this->_pagerMetaLinks($reportsPageCount, $page, '/reportaze/p/');
        
        $view->set('reports', $reports)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/reportaze')
                ->set('pagecount', $reportsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Reportáže');
    }

    /**
     * Show report detail
     * 
     * @param string $urlKey
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        
        $report = App_Model_Report::fetchByUrlKey($urlKey);
        
        if($report === null){
            self::redirect('/nenalezeno');
        }
        
        $this->_checkMetaData($layoutView, $report);
        $view->set('report', $report);
    }

}
