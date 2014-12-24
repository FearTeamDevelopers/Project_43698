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
        $latestActions = App_Model_Action::fetchWithLimit(10);
        $latestReports = App_Model_Report::fetchWithLimit(10);
        $latestGalleries = App_Model_Gallery::fetchWithLimit(10);
        
        $view->set('latestnews', $latestNews)
                ->set('latestreports', $latestReports)
                ->set('latestgalleries', $latestGalleries)
                ->set('latestactions', $latestActions);
    }

}
