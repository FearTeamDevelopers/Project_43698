<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

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

        $view->set('photos', App_Model_Photo::all(array('galleryId = ?' => 1, 'active = ?' => true)))
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

            $report = new App_Model_Report(array(
                'title' => RequestMethods::post('title'),
                'userId' => $this->getUser()->getId(),
                'urlKey' => $urlKey,
                'approved' => 0,
                'archive' => 0,
                'shortBody' => RequestMethods::post('shorttext'),
                'body' => RequestMethods::post('text'),
                'expirationDate' => RequestMethods::post('expiration'),
                'rank' => RequestMethods::post('rank', 1),
                'keywords' => RequestMethods::post('keywords'),
                'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
                'metaDescription' => RequestMethods::post('metadescription', RequestMethods::post('shorttext')),
                'metaImage' => RequestMethods::post('metaimage')
            ));

            if (empty($errors) && $report->validate()) {
                $id = $report->save();

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
                ->set('photos', App_Model_Photo::all(array('galleryId = ?' => 1, 'active = ?' => true)));

        if (RequestMethods::post('submitEditReport')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/report/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($report->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $report->title = RequestMethods::post('title');
            $report->urlKey = $urlKey;
            $report->expirationDate = RequestMethods::post('expiration');
            $report->body = RequestMethods::post('text');
            $report->shortBody = RequestMethods::post('shorttext');
            $report->rank = RequestMethods::post('rank', 1);
            $report->active = RequestMethods::post('active');
            $report->keywords = RequestMethods::post('keywords');
            $report->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
            $report->metaDescription = RequestMethods::post('metadescription', RequestMethods::post('shorttext'));
            $report->metaImage = RequestMethods::post('metaimage');

            if (empty($errors) && $report->validate()) {
                $report->save();

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

        if ($this->checkCSRFToken()) {
            $report = App_Model_Report::first(
                            array('id = ?' => (int) $id), array('id', 'userId')
            );

            if ($this->_security->isGranted('role_admin') !== true ||
                    $report->getUserId() !== $this->getUser()->getId()) {
                echo self::ERROR_MESSAGE_4;
            }

            if (NULL === $report) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($report->delete()) {
                    Event::fire('admin.log', array('success', 'Report id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Report id: ' . $id));
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

        if ($this->checkCSRFToken()) {
            $report = App_Model_Report::first(array('id = ?' => (int) $id));

            if (NULL === $report) {
                echo self::ERROR_MESSAGE_2;
            } else {
                $report->approved = 1;

                if ($report->validate()) {
                    $report->save();

                    Event::fire('admin.log', array('success', 'Report id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Report id: ' . $id));
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
    public function rejectReport($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $report = App_Model_Report::first(array('id = ?' => (int) $id));

            if (NULL === $report) {
                echo self::ERROR_MESSAGE_2;
            } else {
                $report->approved = 2;

                if ($report->validate()) {
                    $report->save();

                    Event::fire('admin.log', array('success', 'Report id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Report id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
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

                            if ($_report->validate()) {
                                $_report->save();
                            } else {
                                $errors[] = "Report id {$_report->getId()} - {$_report->getTitle()} errors: "
                                        . join(', ', $_report->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
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

                            if ($_report->validate()) {
                                $_report->save();
                            } else {
                                $errors[] = "Report id {$_report->getId()} - {$_report->getTitle()} errors: "
                                        . join(', ', $_report->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
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
