<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Model\Model;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class App_Controller_Index extends Controller
{

    /**
     * Check if are sets specific metadata or leave their default values
     */
    private function _checkMetaData($layoutView, Model $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        $canonical = "http://{$this->getServerHost()}{$uri}";
        
        $layoutView->set('canonical', $canonical)
                ->set('metaogurl', "http://{$this->getServerHost()}{$uri}")
                ->set('metaogtype', 'website');
    }

    /**
     * 
     */
    public function index()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $canonical = 'http://' . $this->getServerHost();
        
        $cachedNews = $this->getCache()->get('index-news');
        
        if($cachedNews !== null){
            $news = $cachedNews;
            unset($cachedNews);
        }else{
            $news = App_Model_News::fetchActiveWithLimit(5);
            $this->getCache()->set('index-news', $news);
        }
        
        $cachedActions = $this->getCache()->get('index-actions');
        
        if($cachedActions !== null){
            $actions = $cachedActions;
            unset($cachedActions);
        }else{
            $actions = App_Model_Action::fetchActiveWithLimit(5);
            $this->getCache()->set('index-actions', $actions);
        }
        
        $cachedReports = $this->getCache()->get('index-reports');
        
        if($cachedReports !== null){
            $reports = $cachedReports;
            unset($cachedReports);
        }else{
            $reports = App_Model_Report::fetchActiveWithLimit(10);
            $this->getCache()->set('index-reports', $reports);
        }
            
        $view->set('news', $news)
                ->set('actions', $actions)
                ->set('reports', $reports);
        
        $layoutView->set('canonical', $canonical);
    }

    /**
     * 
     */
    public function loadContent($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $content = $this->getCache()->get('content_' . $urlKey);

        if ($content !== null) {
            $content = $content;
        } else {
            $content = App_Model_PageContent::fetchByUrlKey($urlKey);

            if ($content === null) {
                self::redirect('/nenalezeno');
            }
            
            $this->getCache()->set('content_' . $urlKey, $content);
        }
        
        $this->_checkMetaData($layoutView, $content);
        
        $view->set('content', $content);
    }

    /**
     * 
     */
    public function notFound()
    {
        
    }

    /**
     * 
     */
    public function search()
    {
        $searchString = StringMethods::fastClean(RequestMethods::get('hledat'));
    }
}
