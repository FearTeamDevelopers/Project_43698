<?php

namespace Admin\Controller;

use Admin\Etc\Controller;

/**
 *
 */
class IndexController extends Controller
{
    /**
     * Get some basic info for dashboard.
     *
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();

        $imessages = \Admin\Model\ImessageModel::fetchActive();
        $latestNews = \App\Model\NewsModel::fetchWithLimit(10);
        $latestActions = \App\Model\ActionModel::fetchWithLimit(10);
        $latestReports = \App\Model\ReportModel::fetchWithLimit(10);
        $latestComments = \App\Model\CommentModel::fetchWithLimit(10);

        $latestUsers = $latestErrors = array();

        if($this->isSuperAdmin()){
            $latestErrors = \Admin\Model\AdminLogModel::fetchErrorsFromLastWeek();
            $latestUsers = \App\Model\UserModel::fetchLates(5);
        }elseif($this->isAdmin()){
            $latestUsers = \App\Model\UserModel::fetchLates(5);
        }

        $view->set('latestnews', $latestNews)
                ->set('latestreports', $latestReports)
                ->set('latestusers', $latestUsers)
                ->set('latestcomments', $latestComments)
                ->set('latestactions', $latestActions)
                ->set('latesterrors', $latestErrors)
                ->set('imessages', $imessages);
    }
}
