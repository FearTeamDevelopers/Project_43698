<?php

use Admin\Etc\Controller;

/**
 * 
 */
class Admin_Controller_Index extends Controller
{

    /**
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();
        
        $latestNews = App_Model_News::fetchWithLimit(10);
        $latestNews1 = array_slice($latestNews, 0, count($latestNews)/2);
        $latestNews2 = array_slice($latestNews, count($latestNews)/2, count($latestNews));
        
        $latestActions = App_Model_Action::fetchWithLimit(10);
        $latestReports = App_Model_Report::fetchWithLimit(10);
        
        $view->set('latestnews1', $latestNews1)
                ->set('latestnews2', $latestNews2)
                ->set('latestreports', $latestReports)
                ->set('latestactions', $latestActions);
    }

}
