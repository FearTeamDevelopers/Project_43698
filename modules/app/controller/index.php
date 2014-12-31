<?php

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Model\Model;
use THCFrame\Request\Request;

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
            $news = App_Model_News::fetchActiveWithLimit(4);
            $this->getCache()->set('index-news', $news);
        }
        
        $cachedActions = $this->getCache()->get('index-actions');
        
        if($cachedActions !== null){
            $actions = $cachedActions;
            unset($cachedActions);
        }else{
            $actions = App_Model_Action::fetchActiveWithLimit(6);
            $this->getCache()->set('index-actions', $actions);
        }
        
        $cachedReports = $this->getCache()->get('index-reports');
        
        if($cachedReports !== null){
            $reports = $cachedReports;
            unset($cachedReports);
        }else{
            $reports = App_Model_Report::fetchActiveWithLimit(7);
            $this->getCache()->set('index-reports', $reports);
        }
        
        $cachedPartners = $this->getCache()->get('index-partners');
        
        if($cachedPartners !== null){
            $partners = $cachedPartners;
            unset($cachedPartners);
        }else{
            $partners = App_Model_Partner::all(
                    array('active = ?' => true), 
                    array('*'), 
                    array('rank' => 'desc', 'created' => 'desc')
            );
            $this->getCache()->set('index-partners', $partners);
        }
            
        $view->set('news', $news)
                ->set('actions', $actions)
                ->set('partners', $partners)
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
        $view = $this->getActionView();
        
        $url = 'http://'.$this->getServerHost().'/dosearch';
        $parameters = array('str' => RequestMethods::get('str'));

        $request = new Request();
        $response = $request->request('post', $url, $parameters);
        $urls = json_decode($response, true);

        $view->set('urls', $urls);
    }
}
