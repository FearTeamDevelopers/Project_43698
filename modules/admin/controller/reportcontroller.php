<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Filesystem\FileManager;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class ReportController extends Controller
{

    private $_errors = array();

    /**
     * Check whether user has access to report or not
     * 
     * @param \App\Model\ReportModel $report
     * @return boolean
     */
    private function _checkAccess(\App\Model\ReportModel $report)
    {
        if ($this->isAdmin() === true ||
                $report->getUserId() == $this->getUser()->getId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check whether report unique identifier already exist or not
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = \App\Model\ReportModel::first(array('urlKey = ?' => $key));

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create and return new report object
     * 
     * @return \App\Model\ReportModel
     */
    private function _createObject()
    {
        $urlKey = $urlKeyCh = $this->_createUrlKey(RequestMethods::post('title'));

        for ($i = 1; $i <= 50; $i++) {
            if ($this->_checkUrlKey($urlKeyCh)) {
                break;
            } else {
                $urlKeyCh = $urlKey . '-' . $i;
            }

            if ($i == 50) {
                $this->_errors['title'] = array($this->lang('ARTICLE_UNIQUE_ID'));
                break;
            }
        }

        $fileManager = new FileManager(array(
            'thumbWidth' => $this->getConfig()->thumb_width,
            'thumbHeight' => $this->getConfig()->thumb_height,
            'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
            'maxImageWidth' => $this->getConfig()->photo_maxwidth,
            'maxImageHeight' => $this->getConfig()->photo_maxheight
        ));

        $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $urlKey, 'report', time() . '_')->getUploadErrors();
        $files = $fileManager->getUploadedFiles();

        if (!empty($fileErrors)) {
            $this->_errors['croppedimage'] = $fileErrors;
        }

        if (!empty($files)) {
            foreach ($files as $i => $file) {
                if ($file instanceof \THCFrame\Filesystem\Image) {
                    $imgMain = trim($file->getFilename(), '.');
                    $imgThumb = trim($file->getThumbname(), '.');
                    break;
                }
            }
        } else {
            $imgMain = '';
            $imgThumb = '';
        }

        $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), 
                array('/reportaz/r/' . $urlKey, '[Celý článek]'), 
                RequestMethods::post('shorttext'));

        $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

        $report = new \App\Model\ReportModel(array(
            'title' => RequestMethods::post('title'),
            'userId' => $this->getUser()->getId(),
            'userAlias' => $this->getUser()->getWholeName(),
            'urlKey' => $urlKeyCh,
            'approved' => $this->getConfig()->report_autopublish,
            'archive' => 0,
            'shortBody' => $shortText,
            'body' => RequestMethods::post('text'),
            'rank' => RequestMethods::post('rank', 1),
            'keywords' => $keywords,
            'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
            'metaDescription' => RequestMethods::post('metadescription'),
            'metaImage' => $imgMain,
            'photoName' => $urlKey,
            'imgMain' => $imgMain,
            'imgThumb' => $imgThumb
        ));

        return $report;
    }

    /**
     * Edit existing report object
     * 
     * @param \App\Model\ReportModel $object
     * @return \\App\Model\ReportModel
     */
    private function _editObject(\App\Model\ReportModel $object)
    {
        $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

        if ($object->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
            $this->_errors['title'] = array($this->lang('ARTICLE_TITLE_IS_USED'));
        }

        $fileManager = new FileManager(array(
            'thumbWidth' => $this->getConfig()->thumb_width,
            'thumbHeight' => $this->getConfig()->thumb_height,
            'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
            'maxImageWidth' => $this->getConfig()->photo_maxwidth,
            'maxImageHeight' => $this->getConfig()->photo_maxheight
        ));

        $imgMain = $imgThumb = '';
        if ($object->imgMain == '') {
            $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $urlKey, 'report', time() . '_')->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                $this->_errors['croppedimage'] = $fileErrors;
            }

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $imgMain = trim($file->getFilename(), '.');
                        $imgThumb = trim($file->getThumbname(), '.');
                        break;
                    }
                }
            }
        } else {
            $imgMain = $object->imgMain;
            $imgThumb = $object->imgThumb;
        }

        if (null === $object->userId) {
            $object->userId = $this->getUser()->getId();
            $object->userAlias = $this->getUser()->getWholeName();
        }

        $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), array('/reportaz/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext')
        );

        $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

        $object->title = RequestMethods::post('title');
        $object->urlKey = $urlKey;
        $object->body = RequestMethods::post('text');
        $object->shortBody = $shortText;
        $object->rank = RequestMethods::post('rank', 1);
        $object->active = RequestMethods::post('active');
        $object->approved = RequestMethods::post('approve');
        $object->archive = RequestMethods::post('archive');
        $object->keywords = $keywords;
        $object->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
        $object->metaDescription = RequestMethods::post('metadescription');
        $object->metaImage = $imgMain;
        $object->photoName = $urlKey;
        $object->imgMain = $imgMain;
        $object->imgThumb = $imgThumb;

        return $object;
    }

    /**
     * Check if there is object used for preview saved in session
     * 
     * @return \App\Model\ReportModel
     */
    private function _checkForObject()
    {
        $session = Registry::get('session');
        $report = $session->get('reportPreview');
        $session->erase('reportPreview');

        return $report;
    }

    /**
     * Send email notification abou new report published on web
     */
    private function _sendEmailNotification(\App\Model\ReportModel $report)
    {
        if($report->getApproved() && $this->getConfig()->report_new_notification){
            $users = \App\Model\UserModel::all(array('getNewReportNotification = ?' => true), array('email'));

            $emailTemplate = \Admin\Model\EmailTemplateModel::first(array('title = ?' => 'Nova reportaz'));
            $emailBody = str_replace(array('{TITLE}', '{LINK}'), 
                            array($report->getTitle(), RequestMethods::server('HTTP_HOST').'/reportaze/r/'.$report->getUrlKey()), 
                            $emailTemplate->getBody());

            if(!empty($users)){
                foreach($users as $user){
                    $this->_sendEmail($emailBody, 'Hastrman - Reportáž - '.$report->getTitle(), $user->getEmail());
                }
            }
        }
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
     * Create new report
     * 
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();
        $report = $this->_checkForObject();

        $reportConcepts = \Admin\Model\ConceptModel::all(array(
                    'userId = ?' => $this->getUser()->getId(),
                    'type = ?' => \Admin\Model\ConceptModel::CONCEPT_TYPE_REPORT),
                array('id', 'created', 'modified'), array('created' => 'DESC'), 10);

        $view->set('report', $report)
                ->set('concepts', $reportConcepts);

        if (RequestMethods::post('submitAddReport')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/admin/report/');
            }

            $report = $this->_createObject();

            if (empty($this->_errors) && $report->validate()) {
                $id = $report->save();
                $this->_sendEmailNotification($report);
                $this->getCache()->invalidate();
                \Admin\Model\ConceptModel::deleteAll(array('id = ?' => RequestMethods::post('conceptid')));

                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/report/');
            } else {
                Event::fire('admin.log', array('fail', 'Errors: ' . json_encode($this->_errors + $report->getErrors())));
                $view->set('errors', $this->_errors + $report->getErrors())
                        ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                        ->set('report', $report)
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }

        if (RequestMethods::post('submitPreviewReport')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/admin/report/');
            }

            $report = $this->_createObject();

            if (empty($this->_errors) && $report->validate()) {
                $session = Registry::get('session');
                $session->set('reportPreview', $report);
                $session->set('reportPreviewPhoto', array($report->imgMain, $report->imgThumb));
                \Admin\Model\ConceptModel::deleteAll(array('id = ?' => RequestMethods::post('conceptid')));

                self::redirect('/report/preview?action=add');
            } else {
                $view->set('errors', $this->_errors + $report->getErrors())
                        ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                        ->set('report', $report)
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }
    }

    /**
     * Edit existing report
     * 
     * @before _secured, _participant
     * @param int   $id     report id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $report = $this->_checkForObject();

        if (null === $report) {
            $report = \App\Model\ReportModel::first(array('id = ?' => (int) $id));

            if (null === $report) {
                $view->warningMessage($this->lang('NOT_FOUND'));
                $this->_willRenderActionView = false;
                self::redirect('/admin/report/');
            }

            if (!$this->_checkAccess($report)) {
                $view->warningMessage($this->lang('LOW_PERMISSIONS'));
                $this->_willRenderActionView = false;
                self::redirect('/admin/report/');
            }
        }
        
        $reportConcepts = \Admin\Model\ConceptModel::all(array(
                    'userId = ?' => $this->getUser()->getId(),
                    'type = ?' => \Admin\Model\ConceptModel::CONCEPT_TYPE_REPORT),
                array('id', 'created', 'modified'), array('created' => 'DESC'), 10);

        $view->set('report', $report)
                ->set('concepts', $reportConcepts);

        if (RequestMethods::post('submitEditReport')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/admin/report/');
            }

            $originalReport = clone $report;
            $report = $this->_editObject($report);

            if (empty($this->_errors) && $report->validate()) {
                $report->save();
                \Admin\Model\ReportHistoryModel::logChanges($originalReport, $report);
                $this->getCache()->invalidate();
                \Admin\Model\ConceptModel::deleteAll(array('id = ?' => RequestMethods::post('conceptid')));

                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/report/');
            } else {
                Event::fire('admin.log', array('fail', 'Report id: ' . $id,
                    'Errors: ' . json_encode($this->_errors + $report->getErrors())));
                $view->set('errors', $this->_errors + $report->getErrors())
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }

        if (RequestMethods::post('submitPreviewReport')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/admin/report/');
            }

            $report = $this->_editObject($report);

            if (empty($this->_errors) && $report->validate()) {
                $session = Registry::get('session');
                $session->set('reportPreview', $report);

                self::redirect('/report/preview?action=edit');
            } else {
                $view->set('errors', $this->_errors + $report->getErrors())
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }
    }

    /**
     * Delete existing report
     * 
     * @before _secured, _participant
     * @param int   $id     report id
     */
    public function delete($id)
    {
        $this->_disableView();

        $report = \App\Model\ReportModel::first(
                        array('id = ?' => (int) $id), array('id', 'userId')
        );

        if (NULL === $report) {
            echo $this->lang('NOT_FOUND');
        } else {
            if ($this->_checkAccess($report)) {
                $imgPath = $report->getUnlinkPath();
                $thumbPath = $report->getUnlinkThumbPath();
                
                if ($report->delete()) {
                    @unlink($imgPath);
                    @unlink($thumbPath);
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('success', 'Report id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Report id: ' . $id));
                    echo $this->lang('COMMON_FAIL');
                }
            } else {
                echo $this->lang('LOW_PERMISSIONS');
            }
        }
    }

    /**
     * Delete report image
     * 
     * @before _secured, _participant
     * @param int   $id     report id
     */
    public function deleteMainPhoto($id)
    {
        $this->_disableView();

        if ($this->_checkCSRFToken()) {
            $report = \App\Model\ReportModel::first(array('id = ?' => (int) $id));

            if (NULL === $report) {
                echo $this->lang('NOT_FOUND');
            } else {
                if (!$this->_checkAccess($report)) {
                    echo $this->lang('LOW_PERMISSIONS');
                }

                @unlink($report->getUnlinkPath());
                @unlink($report->getUnlinkThumbPath());
                $report->imgMain = '';
                $report->imgThumb = '';

                if ($report->validate()) {
                    $report->save();

                    Event::fire('admin.log', array('success', 'Report Id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Report Id: ' . $id));
                    echo $this->lang('COMMON_FAIL');
                }
            }
        } else {
            echo $this->lang('COMMON_FAIL');
        }
    }

    /**
     * Delete image in report preview
     * 
     * @before _secured, _participant
     */
    public function previewDeletePhoto()
    {
        $this->_disableView();

        $session = Registry::get('session');
        $photo = $session->get('reportPreviewPhoto');

        if (null !== $photo && !empty($photo)) {
            for ($i = 0; $i < count($photo); $i++) {
                if (file_exists(APP_PATH . $photo[$i])) {
                    unlink(APP_PATH . $photo[$i]);
                } elseif (file_exists('.' . $photo[$i])) {
                    unlink('.' . $photo[$i]);
                } elseif (file_exists('./' . $photo[$i])) {
                    unlink('./' . $photo[$i]);
                }
            }
            echo 'success';
            exit;
        }
        echo $this->lang('COMMON_FAIL');
        exit;
    }

    /**
     * Approve new report
     * 
     * @before _secured, _admin
     * @param int   $id     report id
     */
    public function approveReport($id)
    {
        $this->_disableView();

        $report = \App\Model\ReportModel::first(array('id = ?' => (int) $id));

        if (NULL === $report) {
            echo $this->lang('NOT_FOUND');
        } else {
            $report->approved = 1;

            if (null === $report->userId) {
                $report->userId = $this->getUser()->getId();
                $report->userAlias = $this->getUser()->getWholeName();
            }

            if ($report->validate()) {
                $report->save();
                $this->_sendEmailNotification($report);
                $this->getCache()->invalidate();

                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Report id: ' . $id,
                    'Errors: ' . json_encode($report->getErrors())));
                echo $this->lang('COMMON_FAIL');
            }
        }
    }

    /**
     * Reject new report
     * 
     * @before _secured, _admin
     * @param int   $id     report id
     */
    public function rejectReport($id)
    {
        $this->_disableView();

        $report = \App\Model\ReportModel::first(array('id = ?' => (int) $id));

        if (NULL === $report) {
            echo $this->lang('NOT_FOUND');
        } else {
            $report->approved = 2;

            if (null === $report->userId) {
                $report->userId = $this->getUser()->getId();
                $report->userAlias = $this->getUser()->getWholeName();
            }

            if ($report->validate()) {
                $report->save();

                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Report id: ' . $id,
                    'Errors: ' . json_encode($report->getErrors())));
                echo $this->lang('COMMON_FAIL');
            }
        }
    }

    /**
     * Return list of reports to insert report link to content
     * 
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $reports = \App\Model\ReportModel::all(array(), array('urlKey', 'title'));

        $view->set('reports', $reports);
    }

    /**
     * Execute basic operation over multiple actions
     * 
     * @before _secured, _admin
     */
    public function massAction()
    {
        $this->_disableView();

        $errors = array();

        $ids = RequestMethods::post('ids');
        $action = RequestMethods::post('action');

        if (empty($ids)) {
            echo $this->lang('NO_ROW_SELECTED');
            return;
        }

        switch ($action) {
            case 'delete':
                $reports = \App\Model\ReportModel::all(
                                array('id IN ?' => $ids), array('id', 'title')
                );

                if (NULL !== $reports) {
                    foreach ($reports as $report) {
                        if (!$report->delete()) {
                            $errors[] = $this->lang('DELETE_FAIL') .' - '. $report->getTitle();
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('delete success', 'Report ids: ' . join(',', $ids)));
                    echo $this->lang('DELETE_SUCCESS');
                } else {
                    Event::fire('admin.log', array('delete fail', 'Errors:' . json_encode($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'activate':
                $reports = \App\Model\ReportModel::all(array(
                            'id IN ?' => $ids,
                            'active = ?' => false
                ));

                if (NULL !== $reports) {
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
                                    . join(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('activate success', 'Report ids: ' . join(',', $ids)));
                    echo $this->lang('ACTIVATE_SUCCESS');
                } else {
                    Event::fire('admin.log', array('activate fail', 'Errors:' . json_encode($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'deactivate':
                $reports = \App\Model\ReportModel::all(array(
                            'id IN ?' => $ids,
                            'active = ?' => true
                ));

                if (NULL !== $reports) {
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
                                    . join(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('deactivate success', 'Report ids: ' . join(',', $ids)));
                    echo $this->lang('DEACTIVATE_SUCCESS');
                } else {
                    Event::fire('admin.log', array('deactivate fail', 'Errors:' . json_encode($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'approve':
                $reports = \App\Model\ReportModel::all(array(
                            'id IN ?' => $ids,
                            'approved IN ?' => array(0, 2)
                ));

                if (NULL !== $reports) {
                    foreach ($reports as $report) {
                        $report->approved = 1;

                        if (null === $report->userId) {
                            $report->userId = $this->getUser()->getId();
                            $report->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($report->validate()) {
                            $report->save();
                            $this->_sendEmailNotification($report);
                        } else {
                            $errors[] = "Action id {$report->getId()} - {$report->getTitle()} errors: "
                                    . join(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('approve success', 'Action ids: ' . join(',', $ids)));
                    $this->getCache()->invalidate();
                    echo $this->lang('UPDATE_SUCCESS');
                } else {
                    Event::fire('admin.log', array('approve fail', 'Errors:' . json_encode($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'reject':
                $reports = \App\Model\ReportModel::all(array(
                            'id IN ?' => $ids,
                            'approved IN ?' => array(0, 1)
                ));

                if (NULL !== $reports) {
                    foreach ($reports as $report) {
                        $report->approved = 2;

                        if (null === $report->userId) {
                            $report->userId = $this->getUser()->getId();
                            $report->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($report->validate()) {
                            $report->save();
                        } else {
                            $errors[] = "Action id {$report->getId()} - {$report->getTitle()} errors: "
                                    . join(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('reject success', 'Action ids: ' . join(',', $ids)));
                    $this->getCache()->invalidate();
                    echo $this->lang('UPDATE_SUCCESS');
                } else {
                    Event::fire('admin.log', array('reject fail', 'Errors:' . json_encode($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            default:
                echo $this->lang('COMMON_FAIL');
                break;
        }
    }

    /**
     * Response for ajax call from datatables plugin
     * 
     * @before _secured, _participant
     */
    public function load()
    {
        $this->_disableView();

        $page = (int) RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "rp.created LIKE '%%?%%' OR rp.userAlias LIKE '%%?%%' OR rp.title LIKE '%%?%%'";

            $query = \App\Model\ReportModel::getQuery(
                            array('rp.id', 'rp.userId', 'rp.userAlias', 'rp.title',
                                'rp.active', 'rp.approved', 'rp.archive', 'rp.created'))
                    ->join('tb_user', 'rp.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
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
                }
            } else {
                $query->order('rp.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $reports = \App\Model\ReportModel::initialize($query);

            $countQuery = \App\Model\ReportModel::getQuery(array('rp.id'))
                    ->join('tb_user', 'rp.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                    ->wheresql($whereCond, $search, $search, $search);

            $reportsCount = \App\Model\ReportModel::initialize($countQuery);
            unset($countQuery);
            $count = count($reportsCount);
            unset($reportsCount);
        } else {
            $query = \App\Model\ReportModel::getQuery(
                            array('rp.id', 'rp.userId', 'rp.userAlias', 'rp.title',
                                'rp.active', 'rp.approved', 'rp.archive', 'rp.created'))
                    ->join('tb_user', 'rp.userId = us.id', 'us', array('us.firstname', 'us.lastname'));

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
                }
            } else {
                $query->order('rp.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $reports = \App\Model\ReportModel::initialize($query);
            $count = \App\Model\ReportModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = array();
        if (null !== $reports) {
            foreach ($reports as $report) {
                $label = '';
                if ($report->active) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelRed'>Neaktivní</span>";
                }

                if ($report->approved == 1) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Schváleno</span>";
                } elseif ($report->approved == 2) {
                    $label .= "<span class='infoLabel infoLabelRed'>Zamítnuto</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelOrange'>Čeká na schválení</span>";
                }

                if ($this->getUser()->getId() == $report->getUserId()) {
                    $label .= "<span class='infoLabel infoLabelGray'>Moje</span>";
                }

                if ($report->archive) {
                    $archiveLabel = "<span class='infoLabel infoLabelGreen'>Ano</span>";
                } else {
                    $archiveLabel = "<span class='infoLabel infoLabelGray'>Ne</span>";
                }

                $arr = array();
                $arr [] = "[ \"" . $report->getId() . "\"";
                $arr [] = "\"" . htmlentities($report->getTitle()) . "\"";
                $arr [] = "\"" . $report->getUserAlias() . "\"";
                $arr [] = "\"" . $report->getCreated() . "\"";
                $arr [] = "\"" . $label . "\"";
                $arr [] = "\"" . $archiveLabel . "\"";
                
                $tempStr = "\"";
                if ($this->isAdmin() || $report->userId == $this->getUser()->getId()) {
                    $tempStr .= "<a href='/admin/report/edit/" . $report->id . "' class='btn btn3 btn_pencil' title='Upravit'></a>";
                    $tempStr .= "<a href='/admin/report/delete/" . $report->id . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }

                if ($this->isAdmin() && $report->approved == 0) {
                    $tempStr .= "<a href='/admin/report/approvereport/" . $report->id . "' class='btn btn3 btn_info ajaxReload' title='Schválit'></a>";
                    $tempStr .= "<a href='/admin/report/rejectreport/" . $report->id . "' class='btn btn3 btn_stop ajaxReload' title='Zamítnout'></a>";
                }

                $arr [] = $tempStr . "\"]";
                $returnArr[] = join(',', $arr);
            }

            $str .= join(',', $returnArr) . "]}";

            echo $str;
        } else {
            $str .= "[ \"\",\"\",\"\",\"\",\"\",\"\",\"\"]]}";

            echo $str;
        }
    }

    /**
     * Show help for report section
     * 
     * @before _secured, _participant
     */
    public function help()
    {
        
    }
    
    /**
     * Load concept into active form
     * 
     * @before _secured, _participant
     */
    public function loadConcept($id)
    {
        $this->_disableView();
        $concept = \Admin\Model\ConceptModel::first(array('id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()));
        
        if(null !== $concept){
            $conceptArr = array(
                'conceptid' => $concept->getId(),
                'title' => $concept->getTitle(),
                'shortbody' => $concept->getShortBody(),
                'body' => $concept->getBody(),
                'keywords' => $concept->getKeywords(),
                'metatitle' => $concept->getMetaTitle(),
                'metadescription' => $concept->getMetaDescription()
            );
            
            echo json_encode($conceptArr);
            exit;
        }else{
            echo 'notfound';
            exit;
        }
    }
}
