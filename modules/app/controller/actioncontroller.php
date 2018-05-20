<?php
namespace App\Controller;

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\View\View;

/**
 *
 */
class ActionController extends Controller
{

    /**
     * Check if are set specific metadata or leave their default values.
     */
    private function _checkMetaData($layoutView, \App\Model\ActionModel $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaTitle() != '') {
            $layoutView->set(View::META_TITLE, 'Akce - ' . $object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set(View::META_DESCRIPTION, $object->getMetaDescription());
        }

        $canonical = $this->getServerHost() . '/akce/r/' . $object->getUrlKey();

        $layoutView->set(View::META_CANONICAL, $canonical)
            ->set('article', 1)
            ->set('articlecreated', $object->getCreated())
            ->set('articlemodified', $object->getModified())
            ->set('metaogurl', "{$this->getServerHost()}{$uri}")
            ->set('metaogtype', 'article');
    }

    /**
     * Get list of actions.
     *
     * @param int $page
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->actions_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/akce';
        } else {
            $canonical = $this->getServerHost() . '/akce/p/' . $page;
        }

        $content = $this->getCache()->get('actions-' . $page);

        if (null !== $content) {
            $actions = $content;
        } else {
            $actions = \App\Model\ActionModel::fetchActiveWithLimit($articlesPerPage, $page);

            $this->getCache()->set('actions-' . $page, $actions);
        }

        $actionCount = \App\Model\ActionModel::count(
                ['active = ?' => true,
                    'approved = ?' => 1,
                    'archive = ?' => false,
                    'startDate >= ?' => date('Y-m-d', time()),]
        );

        $actionsPageCount = ceil($actionCount / $articlesPerPage);

        $actionYears = \App\Model\ActionModel::fetchActionYears();
        $this->pagerMetaLinks($actionsPageCount, $page, '/akce/p/');

        $view->set('actions', $actions)
            ->set('pagerpathprefix', '/akce')
            ->set('actionyears', $actionYears)
            ->set('currentpage', $page)
            ->set('pagecount', $actionsPageCount);

        $layoutView->setBasicMeta('Hastrman - Akce', $canonical);
    }

    /**
     * Show action detail.
     *
     * @param string $urlKey
     */
    public function detail($urlKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $action = \App\Model\ActionModel::fetchByUrlKey($urlKey);

        if ($action === null) {
            self::redirect('/nenalezeno');
        }

        $this->_checkMetaData($layoutView, $action);

        if ($this->getUser() !== null) {
            $authUserAttendance = \App\Model\AttendanceModel::fetchTypeByUserAndAction($this->getUser()->getId(), $action->getId());
            $attendance = \App\Model\AttendanceModel::fetchUsersByActionIdSimpleArr($action->getId());
            $comments = \App\Model\CommentModel::fetchCommentsByResourceAndType($action->getId(), \App\Model\CommentModel::RESOURCE_ACTION);

            $view->set('action', $action)
                ->set('newcomment', null)
                ->set('comments', $comments)
                ->set('authuseratt', $authUserAttendance)
                ->set('attendance', $attendance);
        } else {
            $view->set('action', $action)
                ->set('newcomment', null)
                ->set('comments', null)
                ->set('authuseratt', null)
                ->set('attendance', null);
        }

        if (RequestMethods::post('submitAddComment')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true ||
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/akce/r/' . $action->getId());
            }

            $comment = new \App\Model\CommentModel([
                'userId' => $this->getUser()->getId(),
                'resourceId' => $action->getId(),
                'replyTo' => RequestMethods::post('replyTo', 0),
                'type' => \App\Model\CommentModel::RESOURCE_ACTION,
                'body' => RequestMethods::post('text'),
            ]);

            if ($comment->validate()) {
                $id = $comment->save();

                $this->getCache()->clearCache();

                Event::fire('app.log', ['success', 'Comment id: ' . $id . ' from user: ' . $this->getUser()->getId()]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/akce/r/' . $action->getId());
            } else {
                Event::fire('app.log', ['fail', 'Errors: ' . json_encode($comment->getErrors())]);
                $view->set('errors', $comment->getErrors())
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('newcomment', $comment);
            }
        }
    }

    /**
     * Show archivated actions.
     *
     * @param int $year
     * @param int $page
     */
    public function archive($year, $page = 1)
    {
        if (empty($year) || !is_numeric($year)) {
            $year = date('Y');
        }

        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->actions_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/akce/archiv/' . $year;
        } else {
            $canonical = $this->getServerHost() . '/akce/archiv/' . $year . '/p/' . $page;
        }

        $content = $this->getCache()->get('actions-arch-' . $year . '-' . $page);

        if (null !== $content) {
            $actions = $content;
        } else {
            $actions = \App\Model\ActionModel::fetchArchivatedWithLimit($year, $page, $articlesPerPage);

            $this->getCache()->set('actions-arch-' . $year . '-' . $page, $actions);
        }

        $actionCount = \App\Model\ActionModel::count(
                ['active = ?' => true,
                    'approved = ?' => 1,
                    'startDate < ?' => date('Y-m-d'),
                    'startDate >= ?' => $year . '-01-01',
                    'endDate <= ?' => $year . '-12-31',
                ]
        );

        $actionsPageCount = ceil($actionCount / $articlesPerPage);

        $this->pagerMetaLinks($actionsPageCount, $page, '/akce/archiv/' . $year . '/p/');

        $view->set('actions', $actions)
            ->set('pagerpathprefix', '/akce/archiv/' . $year)
            ->set('currentpage', $page)
            ->set('currentyear', $year)
            ->set('pagecount', $actionsPageCount);

        $layoutView->setBasicMeta('Hastrman - Akce - Archiv', $canonical);
    }

    /**
     * Preview of action created in administration but not saved into db.
     *
     * @before _secured, _participant
     */
    public function preview()
    {
        $view = $this->getActionView();
        $session = Registry::get('session');

        $action = $session->get('actionPreview');

        if (null === $action) {
            $this->willRenderActionView = false;
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/action/');
        }

        $act = RequestMethods::get('action');

        $view->set('action', $action)
            ->set('act', $act);
    }

    /**
     * Set user attendance to this action
     *
     * @param int $actionId
     * @param int $type
     * @before _secured, _member
     */
    public function attendance($actionId, $type)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $view = $this->getActionView();

        if (!in_array($type, [\App\Model\AttendanceModel::ACCEPT, \App\Model\AttendanceModel::REJECT, \App\Model\AttendanceModel::MAYBE])) {
            Event::fire('app.log', ['fail', 'Errors: Invalid attendance type - ' . $type]);
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            exit;
        }

        $action = \App\Model\ActionModel::first(['id = ?' => (int) $actionId]);

        if (null === $action) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            \App\Model\AttendanceModel::deleteAll(['userId = ?' => $this->getUser()->getId(), 'actionId = ?' => $action->getId()]);

            $attendance = new \App\Model\AttendanceModel([
                'userId' => $this->getUser()->getId(),
                'actionId' => $action->getId(),
                'type' => (int) $type,
                'comment' => RequestMethods::post('attcomment'),
            ]);

            if ($attendance->validate()) {
                $attendance->save();

                $view->successMessage($this->lang('CREATE_SUCCESS'));
                Event::fire('app.log', ['success', 'Attendance - ' . $type . ' - action ' . $action->getId() . ' by user: ' . $this->getUser()->getId()]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['status' => 'active']);
            } else {
                Event::fire('app.log', ['fail', 'Errors: ' . json_encode($attendance->getErrors())]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Load more actions to the homepage
     *
     * @param int $lastId
     */
    public function loadMore()
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }
        $lastId = RequestMethods::post('lastId');
        $lastStartDate = RequestMethods::post('lastStartDate');

        if (!$lastId || !$lastStartDate) {
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            exit();
        }

        $actions = \App\Model\ActionModel::fetchMoreActionsToHomepage($lastId, $lastStartDate);

        $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['actions' => $actions]);
    }
}
