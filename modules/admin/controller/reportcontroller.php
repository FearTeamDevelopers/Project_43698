<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Core\StringMethods;
use Admin\Model\Notifications\Email\Report as ReportNotification;

/**
 *
 */
class ReportController extends Controller
{

    /**
     * Check whether user has access to report or not.
     *
     * @param \App\Model\ReportModel $report
     *
     * @return bool
     */
    private function checkAccess(\App\Model\ReportModel $report)
    {
        if ($this->isAdmin() === true ||
                $report->getUserId() == $this->getUser()->getId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if there is object used for preview saved in session.
     *
     * @return \App\Model\ReportModel
     */
    private function checkForObject()
    {
        $session = Registry::get('session');
        $report = $session->get('reportPreview');
        $session->remove('reportPreview');

        return $report;
    }

    /**
     * Get list of all actions. Loaded via datatables ajax.
     * For more check load function.
     *
     * @before _secured, _participant
     */
    public function index()
    {

    }

    /**
     * Create new report.
     *
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();
        $report = $this->checkForObject();

        $reportConcepts = \Admin\Model\ConceptModel::all([
                    'userId = ?' => $this->getUser()->getId(),
                    'type = ?' => \Admin\Model\ConceptModel::CONCEPT_TYPE_REPORT,], ['id', 'created', 'modified'], ['created' => 'DESC'], 10);

        $view->set('report', $report)
                ->set('concepts', $reportConcepts);

        if (RequestMethods::post('submitAddReport')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/report/');
            }

            list($report, $errors) = \App\Model\ReportModel::createFromPost(
                            RequestMethods::getPostDataBag(), ['user' => $this->getUser(), 'config' => $this->getConfig()]
            );

            if (empty($errors) && $report->validate()) {
                $id = $report->save();

                ReportNotification::getInstance()->onCreate($report);

                $this->getCache()->erase('report');

                \Admin\Model\ConceptModel::deleteAll(['id = ?' => RequestMethods::post('conceptid')]);

                Event::fire('admin.log', ['success', 'Report id: ' . $id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/report/');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($errors + $report->getErrors())]);
                $view->set('errors', $errors + $report->getErrors())
                        ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                        ->set('report', $report)
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }

        if (RequestMethods::post('submitPreviewReport')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/report/');
            }

            list($report, $errors) = \App\Model\ReportModel::createFromPost(
                            RequestMethods::getPostDataBag(), ['user' => $this->getUser(), 'config' => $this->getConfig()]
            );

            if (empty($errors) && $report->validate()) {
                $session = Registry::get('session');
                $session->set('reportPreview', $report);
                $session->set('reportPreviewPhoto', [$report->imgMain, $report->imgThumb]);
                \Admin\Model\ConceptModel::deleteAll(['id = ?' => RequestMethods::post('conceptid')]);

                self::redirect('/report/preview?action=add');
            } else {
                $view->set('errors', $errors + $report->getErrors())
                        ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                        ->set('report', $report)
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }
    }

    /**
     * Edit existing report.
     *
     * @before _secured, _participant
     *
     * @param int $id report id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $report = $this->checkForObject();

        if (null === $report) {
            $report = \App\Model\ReportModel::first(['id = ?' => (int) $id]);

            if (null === $report) {
                $view->warningMessage($this->lang('NOT_FOUND'));
                $this->willRenderActionView = false;
                self::redirect('/admin/report/');
            }

            if (!$this->checkAccess($report)) {
                $view->warningMessage($this->lang('LOW_PERMISSIONS'));
                $this->willRenderActionView = false;
                self::redirect('/admin/report/');
            }
        }

        $reportConcepts = \Admin\Model\ConceptModel::all([
                    'userId = ?' => $this->getUser()->getId(),
                    'type = ?' => \Admin\Model\ConceptModel::CONCEPT_TYPE_REPORT,], ['id', 'created', 'modified'], ['created' => 'DESC'], 10);

        $comments = \App\Model\CommentModel::fetchCommentsByResourceAndType($report->getId(), \App\Model\CommentModel::RESOURCE_REPORT);

        $view->set('report', $report)
                ->set('comments', $comments)
                ->set('concepts', $reportConcepts);

        if (RequestMethods::post('submitEditReport')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/report/');
            }

            $originalReport = clone $report;
            list($report, $errors) = \App\Model\ReportModel::editFromPost(
                            RequestMethods::getPostDataBag(), $report, [
                        'user' => $this->getUser(),
                        'isAdmin' => $this->isAdmin(),
                        'config' => $this->getConfig()
                            ]
            );

            if (empty($errors) && $report->validate()) {
                $report->save();

//                ReportNotification::getInstance()->onUpdate($report);

                \Admin\Model\ReportHistoryModel::logChanges($originalReport, $report);
                $this->getCache()->erase('report');
                \Admin\Model\ConceptModel::deleteAll(['id = ?' => RequestMethods::post('conceptid')]);

                Event::fire('admin.log', ['success', 'Report id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/report/');
            } else {
                Event::fire('admin.log', ['fail', 'Report id: ' . $id,
                    'Errors: ' . json_encode($errors + $report->getErrors()),]);
                $view->set('errors', $errors + $report->getErrors())
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }

        if (RequestMethods::post('submitPreviewReport')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/report/');
            }

            list($report, $errors) = \App\Model\ReportModel::editFromPost(
                            RequestMethods::getPostDataBag(), $report, [
                        'user' => $this->getUser(),
                        'isAdmin' => $this->isAdmin(),
                        'config' => $this->getConfig()
                            ]
            );

            if (empty($errors) && $report->validate()) {
                $session = Registry::get('session');
                $session->set('reportPreview', $report);

                self::redirect('/report/preview?action=edit');
            } else {
                $view->set('errors', $errors + $report->getErrors())
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }
    }

    /**
     * Delete existing report.
     *
     * @before _secured, _participant
     *
     * @param int $id report id
     */
    public function delete($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $report = \App\Model\ReportModel::first(
                        ['id = ?' => (int) $id], ['id', 'userId']
        );

        if (null === $report) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($this->checkAccess($report)) {
                if ($report->delete()) {
//                    ReportNotification::getInstance()->onDelete($report);

                    $this->getCache()->erase('report');
                    Event::fire('admin.log', ['success', 'Report id: ' . $id]);
                    $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['fail', 'Report id: ' . $id]);
                    $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
                }
            } else {
                $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
            }
        }
    }

    /**
     * Delete report image.
     *
     * @before _secured, _participant
     *
     * @param int $id report id
     */
    public function deleteMainPhoto($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $report = \App\Model\ReportModel::first(['id = ?' => (int) $id]);

        if (null === $report) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if (!$this->checkAccess($report)) {
                $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
            }

            @unlink($report->getUnlinkPath());
            @unlink($report->getUnlinkThumbPath());
            $report->imgMain = '';
            $report->imgThumb = '';

            if ($report->validate()) {
                $report->save();

                Event::fire('admin.log', ['success', 'Report Id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Report Id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Delete image in report preview.
     *
     * @before _secured, _participant
     */
    public function previewDeletePhoto()
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $session = Registry::get('session');
        $photo = $session->get('reportPreviewPhoto');

        if (null !== $photo && !empty($photo)) {
            for ($i = 0; $i < count($photo); ++$i) {
                if (file_exists(APP_PATH . $photo[$i])) {
                    unlink(APP_PATH . $photo[$i]);
                } elseif (file_exists('.' . $photo[$i])) {
                    unlink('.' . $photo[$i]);
                } elseif (file_exists('./' . $photo[$i])) {
                    unlink('./' . $photo[$i]);
                }
            }
            $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
        }
        $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
    }

    /**
     * Approve new report.
     *
     * @before _secured, _admin
     *
     * @param int $id report id
     */
    public function approveReport($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $report = \App\Model\ReportModel::first(['id = ?' => (int) $id]);

        if (null === $report) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $report->approved = \App\Model\ReportModel::STATE_APPROVED;

            if (null === $report->userId) {
                $report->userId = $this->getUser()->getId();
                $report->userAlias = $this->getUser()->getWholeName();
            }

            if ($report->validate()) {
                $report->save();
                
                ReportNotification::getInstance()->onCreate($report);
                
                $this->getCache()->erase('report');

                Event::fire('admin.log', ['success', 'Report id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Report id: ' . $id,
                    'Errors: ' . json_encode($report->getErrors()),]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Reject new report.
     *
     * @before _secured, _admin
     *
     * @param int $id report id
     */
    public function rejectReport($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $report = \App\Model\ReportModel::first(['id = ?' => (int) $id]);

        if (null === $report) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $report->approved = \App\Model\ReportModel::STATE_REJECTED;

            if (null === $report->userId) {
                $report->userId = $this->getUser()->getId();
                $report->userAlias = $this->getUser()->getWholeName();
            }

            if ($report->validate()) {
                $report->save();

                Event::fire('admin.log', ['success', 'Report id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Report id: ' . $id,
                    'Errors: ' . json_encode($report->getErrors()),]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Return list of reports to insert report link to content.
     *
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $reports = \App\Model\ReportModel::all([], ['urlKey', 'title']);

        $view->set('reports', $reports);
    }

    /**
     * Execute basic operation over multiple actions.
     *
     * @before _secured, _admin
     */
    public function massAction()
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
                $reports = \App\Model\ReportModel::all(
                                ['id IN ?' => $ids], ['id', 'title']
                );

                if (null !== $reports) {
                    foreach ($reports as $report) {
                        if ($report->delete()) {
//                            ReportNotification::getInstance()->onDelete($report);
                        } else {
                            $errors[] = $this->lang('DELETE_FAIL') . ' - ' . $report->getTitle();
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['delete report success', 'Report ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('report');
                    $this->ajaxResponse($this->lang('DELETE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['delete report fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'activate':
                $reports = \App\Model\ReportModel::all([
                            'id IN ?' => $ids,
                            'active = ?' => false,
                ]);

                if (null !== $reports) {
                    foreach ($reports as $report) {
                        $report->active = true;

                        if (null === $report->userId) {
                            $report->userId = $this->getUser()->getId();
                            $report->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($report->validate()) {
                            $report->save();
                        } else {
                            $errors[] = "Report id {$report->getId()} - {$report->getTitle()} errors: "
                                    . implode(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['activate report success', 'Report ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('report');
                    $this->ajaxResponse($this->lang('ACTIVATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['activate report fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'deactivate':
                $reports = \App\Model\ReportModel::all([
                            'id IN ?' => $ids,
                            'active = ?' => true,
                ]);

                if (null !== $reports) {
                    foreach ($reports as $report) {
                        $report->active = false;

                        if (null === $report->userId) {
                            $report->userId = $this->getUser()->getId();
                            $report->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($report->validate()) {
                            $report->save();
                        } else {
                            $errors[] = "Report id {$report->getId()} - {$report->getTitle()} errors: "
                                    . implode(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['deactivate report success', 'Report ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('report');
                    $this->ajaxResponse($this->lang('DEACTIVATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['deactivate report fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'approve':
                $reports = \App\Model\ReportModel::all([
                            'id IN ?' => $ids,
                            'approved IN ?' => [0, 2],
                ]);

                if (null !== $reports) {
                    foreach ($reports as $report) {
                        $report->approved = \App\Model\ReportModel::STATE_APPROVED;

                        if (null === $report->userId) {
                            $report->userId = $this->getUser()->getId();
                            $report->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($report->validate()) {
                            $report->save();
                            ReportNotification::getInstance()->onCreate($report);
                        } else {
                            $errors[] = "Report id {$report->getId()} - {$report->getTitle()} errors: "
                                    . implode(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['approve report success', 'Report ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('report');
                    $this->ajaxResponse($this->lang('UPDATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['approve report fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'reject':
                $reports = \App\Model\ReportModel::all([
                            'id IN ?' => $ids,
                            'approved IN ?' => [0, 1],
                ]);

                if (null !== $reports) {
                    foreach ($reports as $report) {
                        $report->approved = \App\Model\ReportModel::STATE_REJECTED;

                        if (null === $report->userId) {
                            $report->userId = $this->getUser()->getId();
                            $report->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($report->validate()) {
                            $report->save();
                        } else {
                            $errors[] = "Report id {$report->getId()} - {$report->getTitle()} errors: "
                                    . implode(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', ['reject report success', 'Report ids: ' . implode(',', $ids)]);
                    $this->getCache()->erase('report');
                    $this->ajaxResponse($this->lang('UPDATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['reject report fail', 'Errors:' . json_encode($errors)]);
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
    public function load()
    {
        $this->disableView();
        $maxRows = 100;

        $page = (int) RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "rp.created LIKE '%%?%%' OR rp.userAlias LIKE '%%?%%' OR rp.title LIKE '%%?%%'";

            $query = \App\Model\ReportModel::getQuery(
                            ['rp.id', 'rp.userId', 'rp.userAlias', 'rp.title',
                                'rp.active', 'rp.approved', 'rp.archive', 'rp.created', 'rp.modified'])
                    ->join('tb_user', 'rp.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                    ->wheresql($whereCond, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('rp.id', $dir);
                } elseif ($column == 1) {
                    $query->order('rp.title', $dir);
                } elseif ($column == 2) {
                    $query->order('rp.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('rp.created', $dir);
                } elseif ($column == 4) {
                    $query->order('rp.modified', $dir);
                }
            } else {
                $query->order('rp.id', 'desc');
            }

            $limit = min((int) RequestMethods::post('iDisplayLength'), $maxRows);
            $query->limit($limit, $page + 1);
            $reports = \App\Model\ReportModel::initialize($query);

            $countQuery = \App\Model\ReportModel::getQuery(['rp.id'])
                    ->join('tb_user', 'rp.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                    ->wheresql($whereCond, $search, $search, $search);

            $reportsCount = \App\Model\ReportModel::initialize($countQuery);
            unset($countQuery);
            $count = count($reportsCount);
            unset($reportsCount);
        } else {
            $query = \App\Model\ReportModel::getQuery(
                            ['rp.id', 'rp.userId', 'rp.userAlias', 'rp.title',
                                'rp.active', 'rp.approved', 'rp.archive', 'rp.created', 'rp.modified'])
                    ->join('tb_user', 'rp.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('rp.id', $dir);
                } elseif ($column == 1) {
                    $query->order('rp.title', $dir);
                } elseif ($column == 2) {
                    $query->order('rp.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('rp.created', $dir);
                } elseif ($column == 4) {
                    $query->order('rp.modified', $dir);
                }
            } else {
                $query->order('rp.id', 'desc');
            }

            $limit = min((int) RequestMethods::post('iDisplayLength'), $maxRows);
            $query->limit($limit, $page + 1);
            $reports = \App\Model\ReportModel::initialize($query);
            $count = \App\Model\ReportModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = [];
        if (null !== $reports) {
            foreach ($reports as $report) {
                $label = '';
                if ($report->active) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelRed'>Neaktivní</span>";
                }

                if ($report->approved == \App\Model\ReportModel::STATE_APPROVED) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Schváleno</span>";
                } elseif ($report->approved == \App\Model\ReportModel::STATE_REJECTED) {
                    $label .= "<span class='infoLabel infoLabelRed'>Zamítnuto</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelOrange'>Čeká na schválení</span>";
                }

                if ($this->getUser()->getId() == $report->getUserId()) {
                    $label .= "<span class='infoLabel infoLabelGray'>Moje</span>";
                }

                if ($report->archive) {
                    $label .= "<span class='infoLabel infoLabelGray'>Archivováno</span>";
                }

                $arr = [];
                $arr [] = '[ "' . $report->getId() . '"';
                $arr [] = '"' . htmlentities($report->getTitle()) . '"';
                $arr [] = '"' . $report->getUserAlias() . '"';
                $arr [] = '"' . $report->getCreated() . '"';
                $arr [] = '"' . $report->getModified() . '"';
                $arr [] = '"' . $label . '"';

                $tempStr = '"';
                if ($this->isAdmin() || $report->userId == $this->getUser()->getId()) {
                    $tempStr .= "<a href='/admin/report/edit/" . $report->id . "#comments' class='btn btn3 btn_chat2' title='Zobrazit komentáře'></a>";
                    $tempStr .= "<a href='/admin/report/edit/" . $report->id . "#basic' class='btn btn3 btn_pencil' title='Upravit'></a>";
                    $tempStr .= "<a href='/admin/report/delete/" . $report->id . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }

                if ($this->isAdmin() && $report->approved == 0) {
                    $tempStr .= "<a href='/admin/report/approvereport/" . $report->id . "' class='btn btn3 btn_info ajaxReload' title='Schválit'></a>";
                    $tempStr .= "<a href='/admin/report/rejectreport/" . $report->id . "' class='btn btn3 btn_stop ajaxReload' title='Zamítnout'></a>";
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
     * Show help for report section.
     *
     * @before _secured, _participant
     */
    public function help()
    {

    }

    /**
     * Load concept into active form.
     *
     * @before _secured, _participant
     */
    public function loadConcept($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $concept = \Admin\Model\ConceptModel::first(['id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()]);

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
