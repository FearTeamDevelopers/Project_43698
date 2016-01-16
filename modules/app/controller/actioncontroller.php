<?php

namespace App\Controller;

use App\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;

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
            $layoutView->set('metatitle', 'Akce - '.$object->getMetaTitle());
        }

        if ($object->getMetaDescription() != '') {
            $layoutView->set('metadescription', $object->getMetaDescription());
        }

        $canonical = 'http://'.$this->getServerHost().'/akce/r/'.$object->getUrlKey();

        $layoutView->set('canonical', $canonical)
                ->set('article', 1)
                ->set('articlecreated', $object->getCreated())
                ->set('articlemodified', $object->getModified())
                ->set('metaogurl', "http://{$this->getServerHost()}{$uri}")
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
            $canonical = 'http://'.$this->getServerHost().'/akce';
        } else {
            $canonical = 'http://'.$this->getServerHost().'/akce/p/'.$page;
        }

        $content = $this->getCache()->get('actions-'.$page);

        if (null !== $content) {
            $actions = $content;
        } else {
            $actions = \App\Model\ActionModel::fetchActiveWithLimit($articlesPerPage, $page);

            $this->getCache()->set('actions-'.$page, $actions);
        }

        $actionCount = \App\Model\ActionModel::count(
                        array('active = ?' => true,
                            'approved = ?' => 1,
                            'archive = ?' => false,
                            'startDate >= ?' => date('Y-m-d', time()), )
        );

        $actionsPageCount = ceil($actionCount / $articlesPerPage);

        $this->pagerMetaLinks($actionsPageCount, $page, '/akce/p/');

        $view->set('actions', $actions)
                ->set('pagerpathprefix', '/akce')
                ->set('currentpage', $page)
                ->set('pagecount', $actionsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Akce');
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

        if($this->getUser() !== null){
            $authUserAttendance = \App\Model\AttendanceModel::fetchTypeByUserAndAction($this->getUser()->getId(), $action->getId());
            $attendance = \App\Model\AttendanceModel::fetchUsersByActionIdSimpleArr($action->getId());
            $comments = \App\Model\CommentModel::fetchCommentsByResourceAndType($action->getId(), \App\Model\CommentModel::RESOURCE_ACTION);

            $view->set('action', $action)
                ->set('newcomment', null)
                ->set('comments', $comments)
                ->set('authuseratt', $authUserAttendance)
                ->set('attendance', $attendance);
        }else{
            $view->set('action', $action)
                ->set('newcomment', null)
                ->set('comments', null)
                ->set('authuseratt', null)
                ->set('attendance', null);
        }

        if (RequestMethods::post('submitAddComment')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->_checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/akce/r/'.$action->getId());
            }

            $comment = new \App\Model\CommentModel(array(
                'userId' => $this->getUser()->getId(),
                'resourceId' => $action->getId(),
                'replyTo' => RequestMethods::post('replyTo', 0),
                'type' => \App\Model\CommentModel::RESOURCE_ACTION,
                'body' => RequestMethods::post('text'),
            ));

            if ($comment->validate()) {
                $id = $comment->save();

                $this->getCache()->invalidate();

                Event::fire('app.log', array('success', 'Comment id: '.$id.' from user: '.$this->getUser()->getId()));
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/akce/r/'.$action->getId());
            } else {
                Event::fire('app.log', array('fail', 'Errors: '.json_encode($comment->getErrors())));
                $view->set('errors', $comment->getErrors())
                    ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                    ->set('newcomment', $comment);
            }
        }
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

        $articlesPerPage = $this->getConfig()->actions_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = 'http://'.$this->getServerHost().'/archiv-akci';
        } else {
            $canonical = 'http://'.$this->getServerHost().'/archiv-akci/p/'.$page;
        }

        $content = $this->getCache()->get('actions-arch-'.$page);

        if (null !== $content) {
            $actions = $content;
        } else {
            $actions = \App\Model\ActionModel::fetchArchivatedWithLimit($articlesPerPage, $page);

            $this->getCache()->set('actions-arch-'.$page, $actions);
        }

        $actionCount = \App\Model\ActionModel::count(
                        array('active = ?' => true,
                            'approved = ?' => 1,
                            'archive = ?' => true, )
        );

        $actionsPageCount = ceil($actionCount / $articlesPerPage);

        $this->pagerMetaLinks($actionsPageCount, $page, '/archiv-akci/p/');

        $view->set('actions', $actions)
                ->set('pagerpathprefix', '/archiv-akci')
                ->set('currentpage', $page)
                ->set('pagecount', $actionsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Akce - Archiv');
    }

    /**
     * Show old but not archivated actions.
     *
     * @param type $page
     */
    public function oldActions($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $articlesPerPage = $this->getConfig()->actions_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = 'http://'.$this->getServerHost().'/probehle-akce';
        } else {
            $canonical = 'http://'.$this->getServerHost().'/probehle-akce/p/'.$page;
        }

        $actions = \App\Model\ActionModel::fetchOldWithLimit($articlesPerPage, $page);

        $actionCount = \App\Model\ActionModel::count(
                        array('active = ?' => true,
                            'approved = ?' => 1,
                            'archive = ?' => false,
                            'startDate <= ?' => date('Y-m-d', time()), )
        );

        $actionsPageCount = ceil($actionCount / $articlesPerPage);

        $this->pagerMetaLinks($actionsPageCount, $page, '/probehle-akce/p/');

        $view->set('actions', $actions)
                ->set('pagerpathprefix', '/probehle-akce')
                ->set('currentpage', $page)
                ->set('pagecount', $actionsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Akce - Proběhlé');
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
            $this->_willRenderActionView = false;
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/action/');
        }

        $act = RequestMethods::get('action');

        $view->set('action', $action)
                ->set('act', $act);
    }

    /**
     * @param type $actionId
     * @param type $type
     * @before _secured, _member
     */
    public function attendance($actionId, $type)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $view = $this->getActionView();

        if ($type != \App\Model\AttendanceModel::ACCEPT &&
                $type != \App\Model\AttendanceModel::REJECT &&
                $type != \App\Model\AttendanceModel::MAYBE) {
            Event::fire('app.log', array('fail', 'Errors: Invalid attendance type - '.$type));
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            exit;
        }

        $action = \App\Model\ActionModel::first(array('id = ?' => (int) $actionId));

        if (null === $action) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            \App\Model\AttendanceModel::deleteAll(array('userId = ?' => $this->getUser()->getId(), 'actionId = ?' => $action->getId()));

            $attendance = new \App\Model\AttendanceModel(array(
                'userId' => $this->getUser()->getId(),
                'actionId' => $action->getId(),
                'type' => (int) $type,
                'comment' => RequestMethods::post('attcomment'),
            ));

            if ($attendance->validate()) {
                $attendance->save();

                $view->successMessage($this->lang('CREATE_SUCCESS'));
                Event::fire('app.log', array('success', 'Attendance - '.$type.' - action '.$action->getId().' by user: '.$this->getUser()->getId()));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, array('status' => 'active'));
            } else {
                Event::fire('app.log', array('fail', 'Errors: '.json_encode($attendance->getErrors())));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }
}
