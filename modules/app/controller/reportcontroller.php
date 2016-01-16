<?php

namespace App\Controller;

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

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
            $layoutView->set('metaogimage', "http://{$this->getServerHost()}{$object->getMetaImage()}");
        }

        if ($object->getMetaTitle() != '') {
            $layoutView->set('metatitle', 'Reportáže - '.$object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        $canonical = 'http://'.$this->getServerHost().'/reportaze/r/'.$object->getUrlKey();

        $layoutView->set('canonical', $canonical)
                ->set('article', 1)
                ->set('articlecreated', $object->getCreated())
                ->set('articlemodified', $object->getModified())
                ->set('metaogurl', "http://{$this->getServerHost()}{$uri}")
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
            $canonical = 'http://'.$this->getServerHost().'/reportaze';
        } else {
            $canonical = 'http://'.$this->getServerHost().'/reportaze/p/'.$page;
        }

        $content = $this->getCache()->get('reports-'.$page);

        if (null !== $content) {
            $reports = $content;
        } else {
            $reports = \App\Model\ReportModel::fetchActiveWithLimit($articlesPerPage, $page);

            $this->getCache()->set('reports-'.$page, $reports);
        }

        $reportCount = \App\Model\ReportModel::count(
                        array('active = ?' => true,
                            'archive = ?' => false,
                            'approved = ?' => 1, )
        );
        $reportsPageCount = ceil($reportCount / $articlesPerPage);

        $this->pagerMetaLinks($reportsPageCount, $page, '/reportaze/p/');

        $view->set('reports', $reports)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/reportaze')
                ->set('pagecount', $reportsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Reportáže');
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
            $canonical = 'http://'.$this->getServerHost().'/archiv-reportazi';
        } else {
            $canonical = 'http://'.$this->getServerHost().'/archiv-reportazi/p/'.$page;
        }

        $content = $this->getCache()->get('report-arch-'.$page);

        if (null !== $content) {
            $reports = $content;
        } else {
            $reports = \App\Model\ReportModel::fetchArchivatedWithLimit($articlesPerPage, $page);

            $this->getCache()->set('report-arch-'.$page, $reports);
        }

        $reportCount = \App\Model\ReportModel::count(
                        array('active = ?' => true,
                            'archive = ?' => true,
                            'approved = ?' => 1, )
        );
        $reportsPageCount = ceil($reportCount / $articlesPerPage);

        $this->pagerMetaLinks($reportsPageCount, $page, '/archiv-reportazi/p/');

        $view->set('reports', $reports)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/archiv-reportazi')
                ->set('pagecount', $reportsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Reportáže - Archiv');
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

        if (RequestMethods::post('submitAddComment')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->_checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/reportaze/r/'.$report->getId());
            }

            $comment = new \App\Model\CommentModel(array(
                'userId' => $this->getUser()->getId(),
                'resourceId' => $report->getId(),
                'replyTo' => RequestMethods::post('replyTo', 0),
                'type' => \App\Model\CommentModel::RESOURCE_REPORT,
                'body' => RequestMethods::post('text'),
            ));

            if ($comment->validate()) {
                $id = $comment->save();

                $this->getCache()->invalidate();

                Event::fire('app.log', array('success', 'Comment id: '.$id.' from user: '.$this->getUser()->getId()));
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/reportaze/r/'.$report->getId());
            } else {
                Event::fire('app.log', array('fail', 'Errors: '.json_encode($comment->getErrors())));
                $view->set('errors', $comment->getErrors())
                    ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                    ->set('newcomment', $comment);
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
            $this->_willRenderActionView = false;
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/report/');
        }

        $act = RequestMethods::get('action');

        $view->set('report', $report)
            ->set('act', $act);
    }
}
