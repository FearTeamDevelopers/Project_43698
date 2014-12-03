<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Filesystem\FileManager;

/**
 * 
 */
class Admin_Controller_Report extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_Report::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     * @return type
     */
    private function _getPhotos()
    {
        return App_Model_Photo::all(array('galleryId = ?' => 1, 'active = ?' => true));
    }

    /**
     * 
     * @return array
     */
    private function _getDocuments()
    {
        return App_Model_Document::all(array('active = ?' => true));
    }

    /**
     * 
     * @return type
     */
    private function _getGalleries()
    {
        return App_Model_Gallery::all(array('active = ?' => true, 'isPublic = ?' => true));
    }

    /**
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();

        $reports = App_Model_Report::all();

        $view->set('reports', $reports);
    }

    /**
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('photos', $this->_getPhotos())
                ->set('galleries', $this->_getGalleries())
                ->set('documents', $this->_getDocuments())
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddReport')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/report/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $cfg = Registry::get('configuration');

            $fileManager = new FileManager(array(
                'thumbWidth' => $cfg->thumb_width,
                'thumbHeight' => $cfg->thumb_height,
                'thumbResizeBy' => $cfg->thumb_resizeby,
                'maxImageWidth' => $cfg->photo_maxwidth,
                'maxImageHeight' => $cfg->photo_maxheight
            ));

            $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $urlKey, 'report', time() . '_')->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                $errors['croppedimage'] = $fileErrors;
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

            $report = new App_Model_Report(array(
                'title' => RequestMethods::post('title'),
                'userId' => $this->getUser()->getId(),
                'userAlias' => $this->getUser()->getWholeName(),
                'urlKey' => $urlKey,
                'approved' => $cfg->report_autopublish,
                'archive' => 0,
                'shortBody' => RequestMethods::post('shorttext'),
                'body' => RequestMethods::post('text'),
                'expirationDate' => RequestMethods::post('expiration'),
                'rank' => RequestMethods::post('rank', 1),
                'keywords' => RequestMethods::post('keywords'),
                'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
                'metaDescription' => RequestMethods::post('metadescription'),
                'metaImage' => RequestMethods::post('metaimage'),
                'photoName' => $urlKey,
                'imgMain' => $imgMain,
                'imgThumb' => $imgThumb
            ));

            if (empty($errors) && $report->validate()) {
                $id = $report->save();

                Registry::get('cache')->invalidate();
                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                $view->successMessage('Report' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/report/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $report->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('report', $report);
            }
        }
    }

    /**
     * @before _secured, _participant
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $report = App_Model_Report::first(array('id = ?' => (int) $id));

        if ($report === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/report/');
        }

        if ($this->_security->isGranted('role_admin') !== true ||
                $report->getUserId() !== $this->getUser()->getId()) {
            $view->warningMessage(self::ERROR_MESSAGE_4);
            self::redirect('/admin/report/');
        }

        $view->set('report', $report)
                ->set('photos', $this->_getPhotos())
                ->set('documents', $this->_getDocuments())
                ->set('galleries', $this->_getGalleries());

        if (RequestMethods::post('submitEditReport')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/report/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($report->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $cfg = Registry::get('configuration');

            $fileManager = new FileManager(array(
                'thumbWidth' => $cfg->thumb_width,
                'thumbHeight' => $cfg->thumb_height,
                'thumbResizeBy' => $cfg->thumb_resizeby,
                'maxImageWidth' => $cfg->photo_maxwidth,
                'maxImageHeight' => $cfg->photo_maxheight
            ));

            $imgMain = $imgThumb = '';
            if ($report->imgMain == '') {
                $fileErrors = $fileManager->uploadBase64Image(RequestMethods::post('croppedimage'), $urlKey, 'report', time() . '_')->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($fileErrors)) {
                    $errors['croppedimage'] = $fileErrors;
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
                $imgMain = $report->imgMain;
                $imgThumb = $report->imgThumb;
            }

            if ($report->userId === null) {
                $report->userId = $this->getUser()->getId();
                $report->userAlias = $this->getUser()->getWholeName();
            }

            $report->title = RequestMethods::post('title');
            $report->urlKey = $urlKey;
            $report->expirationDate = RequestMethods::post('expiration');
            $report->body = RequestMethods::post('text');
            $report->shortBody = RequestMethods::post('shorttext');
            $report->rank = RequestMethods::post('rank', 1);
            $report->active = RequestMethods::post('active');
            $report->approved = RequestMethods::post('approve');
            $report->archive = RequestMethods::post('archive');
            $report->keywords = RequestMethods::post('keywords');
            $report->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
            $report->metaDescription = RequestMethods::post('metadescription');
            $report->metaImage = RequestMethods::post('metaimage');
            $report->photoName = $urlKey;
            $report->imgMain = $imgMain;
            $report->imgThumb = $imgThumb;

            if (empty($errors) && $report->validate()) {
                $report->save();

                Registry::get('cache')->invalidate();
                Event::fire('admin.log', array('success', 'Report id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/report/');
            } else {
                Event::fire('admin.log', array('fail', 'Report id: ' . $id));
                $view->set('errors', $errors + $report->getErrors());
            }
        }
    }

    /**
     * @before _secured, _participant
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
            if ($this->_security->isGranted('role_admin') === true ||
                    $report->getUserId() == $this->getUser()->getId()) {
                if ($report->delete()) {
                    Registry::get('cache')->invalidate();
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
     * @before _secured, _participant
     * @param type $id
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
                if ($this->_security->isGranted('role_admin') !== true ||
                        $report->getUserId() !== $this->getUser()->getId()) {
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
     * @before _secured, _admin
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

            if ($report->userId === null) {
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
     * @before _secured, _admin
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

            if ($report->userId === null) {
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
     * @before _secured, _participant
     */
    public function insertPhotoDialog()
    {
        $this->willRenderLayoutView = false;
    }

    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performReportAction')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/report/');
            }

            $ids = RequestMethods::post('reportids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $report = App_Model_Report::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $report) {
                        foreach ($report as $_report) {
                            if (!$_report->delete()) {
                                $errors[] = 'An error occured while deleting ' . $_report->getTitle();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Registry::get('cache')->invalidate();
                        Event::fire('admin.log', array('delete success', 'Report ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_6);
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/report/');

                    break;
                case 'activate':
                    $report = App_Model_Report::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $report) {
                        foreach ($report as $_report) {
                            $_report->active = true;

                            if ($_report->userId === null) {
                                $_report->userId = $this->getUser()->getId();
                                $_report->userAlias = $this->getUser()->getWholeName();
                            }

                            if ($_report->validate()) {
                                $_report->save();
                            } else {
                                $errors[] = "Report id {$_report->getId()} - {$_report->getTitle()} errors: "
                                        . join(', ', $_report->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Registry::get('cache')->invalidate();
                        Event::fire('admin.log', array('activate success', 'Report ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_4);
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/report/');

                    break;
                case 'deactivate':
                    $report = App_Model_Report::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $report) {
                        foreach ($report as $_report) {
                            $_report->active = false;

                            if ($_report->userId === null) {
                                $_report->userId = $this->getUser()->getId();
                                $_report->userAlias = $this->getUser()->getWholeName();
                            }

                            if ($_report->validate()) {
                                $_report->save();
                            } else {
                                $errors[] = "Report id {$_report->getId()} - {$_report->getTitle()} errors: "
                                        . join(', ', $_report->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Registry::get('cache')->invalidate();
                        Event::fire('admin.log', array('deactivate success', 'Report ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_5);
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/report/');
                    break;
                default:
                    self::redirect('/admin/report/');
                    break;
            }
        }
    }

}
