<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Filesystem\FileManager;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class Admin_Controller_Report extends Controller
{

    private $_errors = array();
            
    /**
     * Check whether user has access to report or not
     * 
     * @param App_Model_Report $report
     * @return boolean
     */
    private function _checkAccess(App_Model_Report $report)
    {
        if($this->_security->isGranted('role_admin') === true ||
                $report->getUserId() == $this->getUser()->getId()){
            return true;
        }else{
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
        $status = App_Model_Report::first(array('urlKey = ?' => $key));

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Create and return new report object
     * 
     * @return \App_Model_Report
     */
    private function _createObject()
    {
        $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

        if (!$this->_checkUrlKey($urlKey)) {
            $this->_errors['title'] = array('This title is already used');
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
                array('/reportaz/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext')
        );

        $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

        $report = new App_Model_Report(array(
            'title' => RequestMethods::post('title'),
            'userId' => $this->getUser()->getId(),
            'userAlias' => $this->getUser()->getWholeName(),
            'urlKey' => $urlKey,
            'approved' => $this->getConfig()->report_autopublish,
            'archive' => 0,
            'shortBody' => $shortText,
            'body' => RequestMethods::post('text'),
            'expirationDate' => RequestMethods::post('expiration'),
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
     * @param App_Model_Report $object
     * @return \App_Model_Report
     */
    private function _editObject(App_Model_Report $object)
    {
        $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

        if ($object->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
            $this->_errors['title'] = array('This title is already used');
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

        $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), 
                array('/reportaz/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext')
        );

        $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

        $object->title = RequestMethods::post('title');
        $object->urlKey = $urlKey;
        $object->expirationDate = RequestMethods::post('expiration');
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
     * @return App_Model_Report
     */
    private function _checkForObject()
    {
        $session = Registry::get('session');
        $report = $session->get('reportPreview');
        $session->erase('reportPreview');
        
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
     * Create new report
     * 
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();
        
        $report = $this->_checkForObject();
        
        if(null !== $report){
            $view->set('report', $report);
        }

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddReport')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/report/');
            }

            $report = $this->_createObject();

            if (empty($this->_errors) && $report->validate()) {
                $id = $report->save();

                $this->getCache()->invalidate();
                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                $view->successMessage('Report' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/report/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $this->_errors + $report->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('report', $report);
            }
        }
        
         if (RequestMethods::post('submitPreviewReport')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/report/');
            }

            $report = $this->_createObject();

            if (empty($this->_errors) && $report->validate()) {
                $session = Registry::get('session');
                $session->set('reportPreview', $report);
                
                self::redirect('/report/preview?action=add');
            } else {
                $view->set('errors', $this->_errors + $report->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('report', $report);
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
        
        if (null !== $report) {
            $view->set('report', $report);
        } else {

            $report = App_Model_Report::first(array('id = ?' => (int) $id));

            if (null === $report) {
                $view->warningMessage(self::ERROR_MESSAGE_2);
                $this->_willRenderActionView = false;
                self::redirect('/admin/report/');
            }

            if (!$this->_checkAccess($report)) {
                $view->warningMessage(self::ERROR_MESSAGE_4);
                $this->_willRenderActionView = false;
                self::redirect('/admin/report/');
            }

            $view->set('report', $report);
        }

        if (RequestMethods::post('submitEditReport')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/report/');
            }

            $report = $this->_editObject($report);

            if (empty($this->_errors) && $report->validate()) {
                $report->save();
                $this->getCache()->invalidate();
                
                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/report/');
            } else {
                Event::fire('admin.log', array('fail', 'Report id: ' . $id));
                $view->set('errors', $this->_errors + $report->getErrors());
            }
        }
        
        if (RequestMethods::post('submitPreviewReport')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/report/');
            }

            $report = $this->_editObject($report);

            if (empty($this->_errors) && $report->validate()) {
                $session = Registry::get('session');
                $session->set('reportPreview', $report);
                
                self::redirect('/report/preview?action=edit');
            } else {
                $view->set('errors', $this->_errors + $report->getErrors());
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
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $report = App_Model_Report::first(
                        array('id = ?' => (int) $id), array('id', 'userId')
        );

        if (NULL === $report) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($this->_checkAccess($report)) {
                if ($report->delete()) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('success', 'Report id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Report id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            } else {
                echo self::ERROR_MESSAGE_4;
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
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $report = App_Model_Report::first(array('id = ?' => (int) $id));

            if (NULL === $report) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if (!$this->_checkAccess($report)) {
                    echo self::ERROR_MESSAGE_4;
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
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * Approve new report
     * 
     * @before _secured, _admin
     * @param int   $id     report id
     */
    public function approveReport($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $report = App_Model_Report::first(array('id = ?' => (int) $id));

        if (NULL === $report) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $report->approved = 1;

            if (null === $report->userId) {
                $report->userId = $this->getUser()->getId();
                $report->userAlias = $this->getUser()->getWholeName();
            }

            if ($report->validate()) {
                $report->save();

                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Report id: ' . $id));
                echo self::ERROR_MESSAGE_1;
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
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $report = App_Model_Report::first(array('id = ?' => (int) $id));

        if (NULL === $report) {
            echo self::ERROR_MESSAGE_2;
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
                Event::fire('admin.log', array('fail', 'Report id: ' . $id));
                echo self::ERROR_MESSAGE_1;
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

        $reports = App_Model_Report::all(array(), array('urlKey', 'title'));

        $view->set('reports', $reports);
    }

    /**
     * Execute basic operation over multiple actions
     * 
     * @before _secured, _admin
     */
    public function massAction()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $errors = array();

        $ids = RequestMethods::post('ids');
        $action = RequestMethods::post('action');

        if (empty($ids)) {
            echo 'Nějaký řádek musí být označen';
            return;
        }

        switch ($action) {
            case 'delete':
                $reports = App_Model_Report::all(
                                array('id IN ?' => $ids), array('id', 'title')
                );

                if (NULL !== $reports) {
                    foreach ($reports as $report) {
                        if (!$report->delete()) {
                            $errors[] = 'An error occured while deleting ' . $report->getTitle();
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('delete success', 'Report ids: ' . join(',', $ids)));
                    echo self::SUCCESS_MESSAGE_6;
                } else {
                    Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'activate':
                $reports = App_Model_Report::all(array(
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
                    echo self::SUCCESS_MESSAGE_4;
                } else {
                    Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'deactivate':
                $reports = App_Model_Report::all(array(
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
                    echo self::SUCCESS_MESSAGE_5;
                } else {
                    Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'approve':
                $reports = App_Model_Report::all(array(
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
                        } else {
                            $errors[] = "Action id {$report->getId()} - {$report->getTitle()} errors: "
                                    . join(', ', $report->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('approve success', 'Action ids: ' . join(',', $ids)));
                    $this->getCache()->invalidate();
                    echo self::SUCCESS_MESSAGE_2;
                } else {
                    Event::fire('admin.log', array('approve fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'reject':
                $reports = App_Model_Report::all(array(
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
                    echo self::SUCCESS_MESSAGE_2;
                } else {
                    Event::fire('admin.log', array('reject fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            default:
                echo self::ERROR_MESSAGE_1;
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
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $page = (int) RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "rp.created LIKE '%%?%%' OR rp.expirationDate LIKE '%%?%%' "
                    . "OR rp.userAlias LIKE '%%?%%' OR rp.title LIKE '%%?%%'";

            $query = App_Model_Report::getQuery(
                            array('rp.id', 'rp.userId', 'rp.userAlias', 'rp.title', 'rp.expirationDate',
                                'rp.active', 'rp.approved', 'rp.archive', 'rp.created'))
                    ->join('tb_user', 'rp.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                    ->wheresql($whereCond, $search, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('rp.id', $dir);
                } elseif ($column == 2) {
                    $query->order('rp.title', $dir);
                } elseif ($column == 3) {
                    $query->order('rp.userAlias', $dir);
                } elseif ($column == 4) {
                    $query->order('rp.expirationDate', $dir);
                } elseif ($column == 5) {
                    $query->order('rp.created', $dir);
                }
            } else {
                $query->order('rp.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $reports = App_Model_Report::initialize($query);

            $countQuery = App_Model_Report::getQuery(array('rp.id'))
                    ->join('tb_user', 'rp.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                    ->wheresql($whereCond, $search, $search, $search, $search);

            $reportsCount = App_Model_Report::initialize($countQuery);
            unset($countQuery);
            $count = count($reportsCount);
            unset($reportsCount);
        } else {
            $query = App_Model_Report::getQuery(
                            array('rp.id', 'rp.userId', 'rp.userAlias', 'rp.title', 'rp.expirationDate',
                                'rp.active', 'rp.approved', 'rp.archive', 'rp.created'))
                    ->join('tb_user', 'rp.userId = us.id', 'us', array('us.firstname', 'us.lastname'));

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('rp.id', $dir);
                } elseif ($column == 2) {
                    $query->order('rp.title', $dir);
                } elseif ($column == 3) {
                    $query->order('rp.userAlias', $dir);
                } elseif ($column == 4) {
                    $query->order('rp.expirationDate', $dir);
                } elseif ($column == 5) {
                    $query->order('rp.created', $dir);
                }
            } else {
                $query->order('rp.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $reports = App_Model_Report::initialize($query);

            $count = App_Model_Report::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = array();
        if (null !== $reports) {
            foreach ($reports as $report) {
                $label = '';
                if ($report->active) {
                    $label .= "<span class='labelProduct labelProductGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='labelProduct labelProductRed'>Neaktivní</span>";
                }

                if ($report->approved == 1) {
                    $label .= "<span class='labelProduct labelProductGreen'>Schváleno</span>";
                } elseif ($report->approved == 2) {
                    $label .= "<span class='labelProduct labelProductRed'>Zamítnuto</span>";
                } else {
                    $label .= "<span class='labelProduct labelProductOrange'>Čeká na schválení</span>";
                }

                if($this->getUser()->getId() == $report->getUserId()){
                    $label .= "<span class='labelProduct labelProductGray'>Moje</span>";
                }

                if ($report->archive) {
                    $archiveLabel = "<span class='labelProduct labelProductGreen'>Ano</span>";
                } else {
                    $archiveLabel = "<span class='labelProduct labelProductGray'>Ne</span>";
                }

                $arr = array();
                $arr [] = "[ \"" . $report->getId() . "\"";
                $arr [] = "\"" . $report->getTitle() . "\"";
                $arr [] = "\"" . $report->getUserAlias() . "\"";
                $arr [] = "\"" . $report->getExpirationDate() . "\"";
                $arr [] = "\"" . $report->getCreated() . "\"";
                $arr [] = "\"" . $label . "\"";
                $arr [] = "\"" . $archiveLabel . "\"";

                $tempStr = "\"<a href='/admin/report/edit/" . $report->id . "' class='btn btn3 btn_pencil' title='Upravit'></a>";

                if ($this->isAdmin() || $report->userId == $this->getUser()->getId()) {
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
            $str .= "[ \"\",\"\",\"\",\"\",\"\",\"\",\"\",\"\"]]}";

            echo $str;
        }
    }

}
