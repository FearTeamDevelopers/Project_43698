<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use Admin\Model\AdminLogModel;
use Admin\Model\ImessageModel;
use App\Model\ActionModel;
use App\Model\CommentModel;
use App\Model\NewsModel;
use App\Model\ReportModel;
use App\Model\UserModel;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\View\Exception\Data;

/**
 *
 */
class IndexController extends Controller
{
    /**
     * Get some basic info for dashboard.
     *
     * @before _secured, _participant
     * @throws Connector
     * @throws Implementation
     * @throws Data
     */
    public function index(): void
    {
        $view = $this->getActionView();

        $imessages = ImessageModel::fetchActive();
        $latestNews = NewsModel::fetchWithLimit(10);
        $latestActions = ActionModel::fetchWithLimit(10);
        $latestReports = ReportModel::fetchWithLimit(10);
        $latestComments = CommentModel::fetchWithLimit(10);

        $latestUsers = $latestErrors = [];

        if ($this->isSuperAdmin()) {
            $latestErrors = AdminLogModel::fetchErrorsFromLastWeek();
            $latestUsers = UserModel::fetchLates(5);
        } elseif ($this->isAdmin()) {
            $latestUsers = UserModel::fetchLates(5);
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
