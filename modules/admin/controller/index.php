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
        
        $latestNews = App_Model_News::fetchLastTen();
        $latestActions = App_Model_Action::fetchLastTen();
        $latestReports = App_Model_Report::fetchLastTen();
        
        $view->set('latestnews', $latestNews)
                ->set('latestreports', $latestReports)
                ->set('latestactions', $latestActions);
    }

}
