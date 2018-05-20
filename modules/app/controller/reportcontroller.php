<?php

namespace App\Controller;

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;
use THCFrame\View\View;

/**
 *
 */
class ReportController extends Controller
{

    /**
     * Check if are set specific metadata or leave their default values.
     */
    private function _checkMetaData($layoutView, \App\Model\ReportModel $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaImage() != '') {
            $layoutView->set('metaogimage', "{$this->getServerHost()}{$object->getMetaImage()}");
        }

        if ($object->getMetaTitle() != '') {
            $layoutView->set(View::META_TITLE, 'Reportáže - ' . $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set(View::META_DESCRIPTION, $object->getMetaDescription());
        }

        $canonical = $this->getServerHost() . '/reportaze/r/' . $object->getUrlKey();

        $layoutView->set(View::META_CANONICAL, $canonical)
                ->set('article', 1)
                ->set('articlecreated', $object->getCreated())
                ->set('articlemodified', $object->getModified())
                ->set('metaogurl', "{$this->getServerHost()}{$uri}")
                ->set('metaogtype', 'article');
    }

    /**
     * Get list of reports.
     *
     * @param int $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->reports_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/reportaze';
        } else {
            $canonical = $this->getServerHost() . '/reportaze/p/' . $page;
        }

        $content = $this->getCache()->get('reports-' . $page);

        if (null !== $content) {
            $reports = $content;
        } else {
            $reports = \App\Model\ReportModel::fetchActiveWithLimit($articlesPerPage, $page);

            $this->getCache()->set('reports-' . $page, $reports);
        }

        $reportCount = \App\Model\ReportModel::count(
                        ['active = ?' => true,
                            'archive = ?' => false,
                            'approved = ?' => 1,]
        );
        $reportsPageCount = ceil($reportCount / $articlesPerPage);

        $this->pagerMetaLinks($reportsPageCount, $page, '/reportaze/p/');

        $view->set('reports', $reports)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/reportaze')
                ->set('pagecount', $reportsPageCount);

        $layoutView->set(View::META_CANONICAL, $canonical)
                ->set(View::META_TITLE, 'Hastrman - Reportáže');
    }

    /**
     * Show archivated actions.
     *
     * @param type $page
     */
    public function archive($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->reports_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/archiv-reportazi';
        } else {
            $canonical = $this->getServerHost() . '/archiv-reportazi/p/' . $page;
        }

        $content = $this->getCache()->get('report-arch-' . $page);

        if (null !== $content) {
            $reports = $content;
        } else {
            $reports = \App\Model\ReportModel::fetchArchivatedWithLimit($articlesPerPage, $page);

            $this->getCache()->set('report-arch-' . $page, $reports);
        }

        $reportCount = \App\Model\ReportModel::count(
                        ['active = ?' => true,
                            'archive = ?' => true,
                            'approved = ?' => 1,]
        );
        $reportsPageCount = ceil($reportCount / $articlesPerPage);

        $this->pagerMetaLinks($reportsPageCount, $page, '/archiv-reportazi/p/');

        $view->set('reports', $reports)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/archiv-reportazi')
                ->set('pagecount', $reportsPageCount);

        $layoutView->set(View::META_CANONICAL, $canonical)
                ->set(View::META_TITLE, 'Hastrman - Reportáže - Archiv');
    }

    /**
     * Show report detail.
     *
     * @param string $urlKey
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $report = \App\Model\ReportModel::fetchByUrlKey($urlKey);

        if ($report === null) {
            self::redirect('/nenalezeno');
        }

        $comments = \App\Model\CommentModel::fetchCommentsByResourceAndType($report->getId(), \App\Model\CommentModel::RESOURCE_NEWS);

        $this->_checkMetaData($layoutView, $report);
        $view->set('report', $report)
                ->set('newcomment', null)
                ->set('comments', $comments);
    }

    /**
     * Add comment to report
     *
     * @before _secured
     *
     * @param type $id
     */
    public function addComment($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true ||
                $this->checkMultiSubmissionProtectionToken() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $report = \App\Model\ReportModel::first(
                        ['id = ?' => (int) $id], ['id', 'userId']
        );

        if (null === $report) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $comment = new \App\Model\CommentModel([
                'userId' => $this->getUser()->getId(),
                'resourceId' => $report->getId(),
                'replyTo' => RequestMethods::post('replyTo', 0),
                'type' => \App\Model\CommentModel::RESOURCE_REPORT,
                'body' => RequestMethods::post('text'),
            ]);

            if ($comment->validate()) {
                $id = $comment->save();
                Event::fire('app.log', ['success', 'Comment id: ' . $id . ' from user: ' . $this->getUser()->getId()]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('app.log', ['fail', 'Errors: ' . json_encode($comment->getErrors())]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Preview of report created in administration but not saved into db.
     *
     * @before _secured, _participant
     */
    public function preview()
    {
        $view = $this->getActionView();
        $session = Registry::get('session');

        $report = $session->get('reportPreview');

        if (null === $report) {
            $this->willRenderActionView = false;
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/report/');
        }

        $act = RequestMethods::get('action');

        $view->set('report', $report)
                ->set('act', $act);
    }

}
