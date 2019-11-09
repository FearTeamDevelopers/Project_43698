<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use Admin\Model\ActionHistoryModel;
use Admin\Model\ConceptModel;
use Admin\Model\Notifications\Email\Action as ActionNotification;
use App\Model\ActionModel;
use App\Model\AttendanceModel;
use App\Model\CommentModel;
use ReflectionException;
use THCFrame\Core\Exception\Argument;
use THCFrame\Core\Exception\Lang;
use THCFrame\Events\Events as Event;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\Model\Exception\Validation;
use THCFrame\Request\RequestMethods;
use THCFrame\View\Exception\Data;

/**
 *
 */
class ActionController extends Controller
{

    /**
     * Get list of all actions. Loaded via datatables ajax.
     * For more check load function.
     *
     * @before _secured, _participant
     */
    public function index(): void
    {

    }

    /**
     * Create new action.
     *
     * @before _secured, _participant
     * @throws Argument
     * @throws Data
     * @throws Lang
     * @throws Connector
     * @throws Implementation
     */
    public function add(): void
    {
        $view = $this->getActionView();
        $action = $this->checkForObject();

        $actionConcepts = ConceptModel::fetchByUserAndType($this->getUser()->getId(),
            ConceptModel::CONCEPT_TYPE_ACTION);

        $view->set('action', $action)
            ->set('concepts', $actionConcepts);

        if (RequestMethods::post('submitAddAction')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/action/');
            }

            [$action, $errors] = ActionModel::createFromPost(
                RequestMethods::getPostDataBag(),
                ['user' => $this->getUser(), 'autoPublish' => $this->getConfig()->action_autopublish]
            );

            if (empty($errors) && $action->validate()) {
                $id = $action->save();

                ActionNotification::getInstance()->onCreate($action);

                $this->getCache()->erase('actions');
                ConceptModel::deleteAll(['id = ?' => RequestMethods::post('conceptid')]);

                Event::fire('admin.log', ['success', 'Action id: ' . $id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/action/');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($errors + $action->getErrors())]);
                $view->set('errors', $errors + $action->getErrors())
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('action', $action)
                    ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }

        if (RequestMethods::post('submitPreviewAction')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/action/');
            }

            [$action, $errors] = ActionModel::createFromPost(
                RequestMethods::getPostDataBag(),
                ['user' => $this->getUser(), 'autoPublish' => $this->getConfig()->action_autopublish]
            );

            if (empty($errors) && $action->validate()) {
                $this->getSession()->set('actionPreview', $action);
                ConceptModel::deleteAll(['id = ?' => RequestMethods::post('conceptid')]);

                self::redirect('/action/preview?action=add');
            } else {
                $view->set('errors', $errors + $action->getErrors())
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('action', $action)
                    ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }
    }

    /**
     * Check if there is object used for preview saved in session.
     *
     * @return ActionModel|null
     */
    private function checkForObject(): ?ActionModel
    {
        $action = $this->getSession()->get('actionPreview');
        $this->getSession()->remove('actionPreview');

        return $action;
    }

    /**
     * Edit existing action.
     *
     * @before _secured, _participant
     *
     * @param int $id action id
     * @throws Argument
     * @throws Data
     * @throws ReflectionException
     * @throws Validation
     * @throws Lang
     * @throws Connector
     * @throws Implementation
     */
    public function edit($id): void
    {
        $view = $this->getActionView();

        $action = $this->checkForObject();

        if (null === $action) {
            $action = ActionModel::first(['id = ?' => (int)$id]);

            if (null === $action) {
                $view->warningMessage($this->lang('NOT_FOUND'));
                $this->willRenderActionView = false;
                self::redirect('/admin/action/');
            }

            if (!$this->checkAccess($action)) {
                $view->warningMessage($this->lang('LOW_PERMISSIONS'));
                $this->willRenderActionView = false;
                self::redirect('/admin/action/');
            }
        }

        $actionConcepts = ConceptModel::fetchByUserAndType($this->getUser()->getId(),
            ConceptModel::CONCEPT_TYPE_ACTION);
        $comments = CommentModel::fetchCommentsByResourceAndType($action->getId(),
            CommentModel::RESOURCE_ACTION);
        $attUsers = AttendanceModel::fetchUsersByActionId($action->getId());

        $view->set('action', $action)
            ->set('comments', $comments)
            ->set('attendance', $attUsers)
            ->set('concepts', $actionConcepts);

        if (RequestMethods::post('submitEditAction')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/action/');
            }

            $originalAction = clone $action;

            [$action, $errors] = ActionModel::editFromPost(
                RequestMethods::getPostDataBag(), $action, [
                    'user' => $this->getUser(),
                    'isAdmin' => $this->isAdmin(),
                    'autoPublish' => $this->getConfig()->action_autopublish,
                ]
            );

            if (empty($errors) && $action->validate()) {
                $action->save();
                ActionHistoryModel::logChanges($originalAction, $action);

//                ActionNotification::getInstance()->onUpdate($action);

                $this->getCache()->erase('actions');

                $conceptId = RequestMethods::post('conceptid', 0);

                if ($conceptId !== 0) {
                    ConceptModel::deleteAll(['id = ?' => $conceptId]);
                }

                Event::fire('admin.log', ['success', 'Action id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/action/');
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Action id: ' . $id,
                    'Errors: ' . json_encode($errors + $action->getErrors()),
                ]);
                $view->set('errors', $errors + $action->getErrors())
                    ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }

        if (RequestMethods::post('submitPreviewAction')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/action/');
            }

            [$action, $errors] = ActionModel::editFromPost(
                RequestMethods::getPostDataBag(), $action, [
                    'user' => $this->getUser(),
                    'isAdmin' => $this->isAdmin(),
                    'autoPublish' => $this->getConfig()->action_autopublish,
                ]
            );

            if (empty($errors) && $action->validate()) {
                $this->getSession()->set('actionPreview', $action);

                self::redirect('/action/preview?action=edit');
            } else {
                $view->set('errors', $errors + $action->getErrors())
                    ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }
    }

    /**
     * Check whether user has access to action or not.
     *
     * @param ActionModel $action
     *
     * @return bool
     */
    private function checkAccess(ActionModel $action): ?bool
    {
        return $this->isAdmin() === true ||
            $action->getUserId() == $this->getUser()->getId();
    }

    /**
     * Delete existing action.
     *
     * @before _secured, _participant
     *
     * @param int $id action id
     * @throws Connector
     * @throws Implementation
     */
    public function delete($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $action = ActionModel::first(
            ['id = ?' => (int)$id], ['id', 'userId']
        );

        if (null === $action) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } elseif ($this->checkAccess($action)) {
            if ($action->delete()) {
                $this->getCache()->erase('actions');
//                    ActionNotification::getInstance()->onDelete($action);

                Event::fire('admin.log', ['success', 'Action id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Action id: ' . $id,
                    'Errors: ' . json_encode($action->getErrors()),
                ]);

                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        } else {
            $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
        }
    }

    /**
     * Approve new action.
     *
     * @before _secured, _admin
     *
     * @param int $id action id
     * @throws Connector
     * @throws Implementation
     */
    public function approveAction($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $action = ActionModel::first(['id = ?' => (int)$id]);

        if (null === $action) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $action->approved = 1;

            if (null === $action->userId) {
                $action->userId = $this->getUser()->getId();
                $action->userAlias = $this->getUser()->getWholeName();
            }

            if ($action->validate()) {
                $action->save();
                ActionNotification::getInstance()->onCreate($action);
                $this->getCache()->erase('actions');

                Event::fire('admin.log', ['success', 'Action id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Action id: ' . $id,
                    'Errors: ' . json_encode($action->getErrors()),
                ]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Reject new action.
     *
     * @before _secured, _admin
     *
     * @param int $id action id
     * @throws Connector
     * @throws Implementation
     */
    public function rejectAction($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $action = ActionModel::first(['id = ?' => (int)$id]);

        if (null === $action) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $action->approved = 2;

            if (null === $action->userId) {
                $action->userId = $this->getUser()->getId();
                $action->userAlias = $this->getUser()->getWholeName();
            }

            if ($action->validate()) {
                $action->save();

                Event::fire('admin.log', ['success', 'Action id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Action id: ' . $id,
                    'Errors: ' . json_encode($action->getErrors()),
                ]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Return list of actions to insert action link to content.
     *
     * @before _secured, _participant
     * @throws Data
     * @throws Connector
     * @throws Implementation
     */
    public function insertToContent(): void
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $actions = ActionModel::all([], ['urlKey', 'title']);

        $view->set('actions', $actions);
    }

    /**
     * Execute basic operation over multiple actions.
     *
     * @before _secured, _admin
     */
    public function massAction(): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $errors = [];

        $ids = RequestMethods::post('ids');
        $action = RequestMethods::post('action');

        if (empty($ids)) {
            $this->ajaxResponse($this->lang('NO_ROW_SELECTED'), true);
        }

        switch ($action) {
            case 'delete':
                $actions = ActionModel::all(
                    ['id IN ?' => $ids], ['id', 'title']
                );

                if (null !== $actions) {
                    foreach ($actions as $action) {
                        if ($action->delete()) {
//                            ActionNotification::getInstance()->onDelete($action);
                        } else {
                            $errors[] = $this->lang('DELETE_FAIL') . ' - ' . $action->getTitle();
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['delete success', 'Action ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('actions');
                    $this->ajaxResponse($this->lang('DELETE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['delete fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'activate':
                $actions = ActionModel::all([
                    'id IN ?' => $ids,
                    'active = ?' => false,
                ]);

                if (null !== $actions) {
                    foreach ($actions as $action) {
                        $action->active = true;

                        if (null === $action->userId) {
                            $action->userId = $this->getUser()->getId();
                            $action->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($action->validate()) {
                            $action->save();
                        } else {
                            $errors[] = "Action id {$action->getId()} - {$action->getTitle()} errors: "
                                . implode(', ', $action->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['activate success', 'Action ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('actions');
                    $this->ajaxResponse($this->lang('ACTIVATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['activate fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'deactivate':
                $actions = ActionModel::all([
                    'id IN ?' => $ids,
                    'active = ?' => true,
                ]);

                if (null !== $actions) {
                    foreach ($actions as $action) {
                        $action->active = false;

                        if (null === $action->userId) {
                            $action->userId = $this->getUser()->getId();
                            $action->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($action->validate()) {
                            $action->save();
                        } else {
                            $errors[] = "Action id {$action->getId()} - {$action->getTitle()} errors: "
                                . implode(', ', $action->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['deactivate success', 'Action ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('actions');
                    $this->ajaxResponse($this->lang('DEACTIVATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['deactivate fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'approve':
                $actions = ActionModel::all([
                    'id IN ?' => $ids,
                    'approved IN ?' => [0, 2],
                ]);

                if (null !== $actions) {
                    foreach ($actions as $action) {
                        $action->approved = 1;

                        if (null === $action->userId) {
                            $action->userId = $this->getUser()->getId();
                            $action->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($action->validate()) {
                            $action->save();
                            ActionNotification::getInstance()->onCreate($action);
                        } else {
                            $errors[] = "Action id {$action->getId()} - {$action->getTitle()} errors: "
                                . implode(', ', $action->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['approve success', 'Action ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('actions');
                    $this->ajaxResponse($this->lang('UPDATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['approve fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'reject':
                $actions = ActionModel::all([
                    'id IN ?' => $ids,
                    'approved IN ?' => [0, 1],
                ]);

                if (null !== $actions) {
                    foreach ($actions as $action) {
                        $action->approved = 2;

                        if (null === $action->userId) {
                            $action->userId = $this->getUser()->getId();
                            $action->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($action->validate()) {
                            $action->save();
                        } else {
                            $errors[] = "Action id {$action->getId()} - {$action->getTitle()} errors: "
                                . implode(', ', $action->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['reject success', 'Action ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('actions');
                    $this->ajaxResponse($this->lang('UPDATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['reject fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            default:
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
                break;
        }
    }

    /**
     * Response for ajax call from datatables plugin.
     *
     * @before _secured, _participant
     */
    public function load(): void
    {
        $this->disableView();
        $maxRows = 100;

        $page = (int)RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "ac.created LIKE '%%?%%' OR ac.userAlias LIKE '%%?%%' OR ac.title LIKE '%%?%%'";

            $query = ActionModel::getQuery(
                [
                    'ac.id',
                    'ac.userId',
                    'ac.userAlias',
                    'ac.title',
                    'ac.startDate',
                    'ac.active',
                    'ac.approved',
                    'ac.archive',
                    'ac.created',
                ])
                ->join('tb_user', 'ac.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->wheresql($whereCond, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('ac.id', $dir);
                } elseif ($column == 1) {
                    $query->order('ac.title', $dir);
                } elseif ($column == 2) {
                    $query->order('ac.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('ac.created', $dir);
                } elseif ($column == 4) {
                    $query->order('ISNULL(ac.startDate), ac.startDate', $dir);
                }
            } else {
                $query->order('ac.id', 'desc');
            }

            $limit = min((int)RequestMethods::post('iDisplayLength'), $maxRows);
            $query->limit($limit, $page + 1);
            $actions = ActionModel::initialize($query);

            $countQuery = ActionModel::getQuery(['ac.id'])
                ->join('tb_user', 'ac.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->wheresql($whereCond, $search, $search, $search);

            $actionsCount = ActionModel::initialize($countQuery);
            unset($countQuery);
            $count = count($actionsCount);
            unset($actionsCount);
        } else {
            $query = ActionModel::getQuery(
                [
                    'ac.id',
                    'ac.userId',
                    'ac.userAlias',
                    'ac.title',
                    'ac.startDate',
                    'ac.active',
                    'ac.approved',
                    'ac.archive',
                    'ac.created',
                ])
                ->join('tb_user', 'ac.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('ac.id', $dir);
                } elseif ($column == 1) {
                    $query->order('ac.title', $dir);
                } elseif ($column == 2) {
                    $query->order('ac.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('ac.created', $dir);
                } elseif ($column == 4) {
                    $query->order('ISNULL(ac.startDate), ac.startDate', $dir);
                }
            } else {
                $query->order('ac.id', 'desc');
            }

            $limit = min((int)RequestMethods::post('iDisplayLength'), $maxRows);
            $query->limit($limit, $page + 1);
            $actions = ActionModel::initialize($query);

            $count = ActionModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = [];
        if (null !== $actions) {
            foreach ($actions as $action) {
                $label = '';
                if ($action->active) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelRed'>Neaktivní</span>";
                }

                if ($action->approved == ActionModel::STATE_APPROVED) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Schváleno</span>";
                } elseif ($action->approved == ActionModel::STATE_REJECTED) {
                    $label .= "<span class='infoLabel infoLabelRed'>Zamítnuto</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelOrange'>Čeká na schválení</span>";
                }

                if ($this->getUser()->getId() == $action->getUserId()) {
                    $label .= "<span class='infoLabel infoLabelGray'>Moje</span>";
                }

                if ($action->archive) {
                    $label .= "<span class='infoLabel infoLabelGray'>Archivováno</span>";
                }

                $arr = [];
                $arr [] = '[ "' . $action->getId() . '"';
                $arr [] = '"' . htmlentities($action->getTitle()) . '"';
                $arr [] = '"' . $action->getUserAlias() . '"';
                $arr [] = '"' . $action->getCreated() . '"';
                $arr [] = '"' . $action->getStartDate() . '"';
                $arr [] = '"' . $label . '"';

                $tempStr = '"';
                if ($this->isAdmin() || $action->userId == $this->getUser()->getId()) {
                    $tempStr .= "<a href='/admin/action/edit/" . $action->id . "#comments' class='btn btn3 btn_chat2' title='Zobrazit komentáře'></a>";
                    $tempStr .= "<a href='/admin/action/edit/" . $action->id . "#attendance' class='btn btn3 btn_users' title='Zobrazit účastníky'></a>";
                    $tempStr .= "<a href='/admin/action/edit/" . $action->id . "#basic' class='btn btn3 btn_pencil' title='Upravit'></a>";
                    $tempStr .= "<a href='/admin/action/delete/" . $action->id . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }

                if ($this->isAdmin() && $action->approved == 0) {
                    $tempStr .= "<a href='/admin/action/approveaction/" . $action->id . "' class='btn btn3 btn_info ajaxReload' title='Schválit'></a>";
                    $tempStr .= "<a href='/admin/action/rejectaction/" . $action->id . "' class='btn btn3 btn_stop ajaxReload' title='Zamítnout'></a>";
                }

                $arr [] = $tempStr . '"]';
                $returnArr[] = implode(',', $arr);
            }

            $str .= implode(',', $returnArr) . ']}';

            echo $str;
        } else {
            $str .= '[ "","","","","","",""]]}';

            echo $str;
        }
    }

    /**
     * Show help for action section.
     *
     * @before _secured, _participant
     */
    public function help(): void
    {

    }

    /**
     * Load concept into active form.
     *
     * @before _secured, _participant
     * @param $id
     * @throws Connector
     * @throws Implementation
     */
    public function loadConcept($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $concept = ConceptModel::first(['id = ?' => (int)$id, 'userId = ?' => $this->getUser()->getId()]);

        if (null !== $concept) {
            $conceptArr = [
                'conceptid' => $concept->getId(),
                'title' => $concept->getTitle(),
                'shortbody' => $concept->getShortBody(),
                'body' => $concept->getBody(),
                'keywords' => $concept->getKeywords(),
                'metatitle' => $concept->getMetaTitle(),
                'metadescription' => $concept->getMetaDescription(),
            ];

            $this->ajaxResponse(json_encode($conceptArr));
        } else {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        }
    }

}
