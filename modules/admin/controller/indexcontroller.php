<?php

namespace Admin\Controller;

use Admin\Etc\Controller;

/**
 * 
 */
class IndexController extends Controller
{

    /**
     * Get some basic info for dashboard
     * 
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();

        $latestNews = \App\Model\NewsModel::fetchWithLimit(10);
        $latestActions = \App\Model\ActionModel::fetchWithLimit(10);
        $latestReports = \App\Model\ReportModel::fetchWithLimit(10);
        $latestGalleries = \App\Model\GalleryModel::fetchWithLimit(10);
        
        $view->set('latestnews', $latestNews)
                ->set('latestreports', $latestReports)
                ->set('latestgalleries', $latestGalleries)
                ->set('latestactions', $latestActions);
    }

}
