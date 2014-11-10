<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class Admin_Controller_Action extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_Action::first(array('urlKey = ?' => $key));

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

        $actions = App_Model_Action::all();

        $view->set('actions', $actions);
    }

    /**
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('photos', App_Model_Photo::all(array('galleryId = ?' => 1, 'active = ?' => true)))
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddAction')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/action/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $action = new App_Model_Action(array(
                'title' => RequestMethods::post('title'),
                'userId' => $this->getUser()->getId(),
                'urlKey' => $urlKey,
                'approved' => 0,
                'archive' => 0,
                'shortBody' => RequestMethods::post('shorttext'),
                'body' => RequestMethods::post('text'),
                'expirationDate' => RequestMethods::post('expiration'),
                'rank' => RequestMethods::post('rank', 1),
                'keywords' => RequestMethods::post('keywords')
            ));

            if (empty($errors) && $action->validate()) {
                $id = $action->save();

                Event::fire('admin.log', array('success', 'Action id: ' . $id));
                $view->successMessage('Action' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/action/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $action->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('action', $action);
            }
        }
    }

    /**
     * @before _secured, _participant
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $action = App_Model_Action::first(array('id = ?' => (int) $id));

        if ($action === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/action/');
        }

        if ($this->_security->isGranted('role_admin') !== true ||
                $action->getUserId() !== $this->getUser()->getId()) {
            $view->warningMessage(self::ERROR_MESSAGE_4);
            self::redirect('/admin/action/');
        }

        $view->set('action', $action)
                ->set('photos', App_Model_Photo::all(array('galleryId = ?' => 1, 'active = ?' => true)));

        if (RequestMethods::post('submitEditAction')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/action/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($action->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $action->title = RequestMethods::post('title');
            $action->urlKey = $urlKey;
            $action->expirationDate = RequestMethods::post('expiration');
            $action->body = RequestMethods::post('text');
            $action->shortBody = RequestMethods::post('shorttext');
            $action->rank = RequestMethods::post('rank', 1);
            $action->active = RequestMethods::post('active');
            $action->keywords = RequestMethods::post('keywords');

            if (empty($errors) && $action->validate()) {
                $action->save();

                Event::fire('admin.log', array('success', 'Action id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/action/');
            } else {
                Event::fire('admin.log', array('fail', 'Action id: ' . $id));
                $view->set('errors', $errors + $action->getErrors());
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
            $action = App_Model_Action::first(
                            array('id = ?' => (int) $id), array('id', 'userId')
            );

            if ($this->_security->isGranted('role_admin') !== true ||
                    $action->getUserId() !== $this->getUser()->getId()) {
                echo self::ERROR_MESSAGE_4;
            }

            if (NULL === $action) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($action->delete()) {
                    Event::fire('admin.log', array('success', 'Action id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Action id: ' . $id));
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
    public function approveAction($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $action = App_Model_Action::first(array('id = ?' => (int) $id));

            if (NULL === $action) {
                echo self::ERROR_MESSAGE_2;
            } else {
                $action->approved = 1;

                if ($action->validate()) {
                    $action->save();

                    Event::fire('admin.log', array('success', 'Action id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Action id: ' . $id));
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
    public function rejectAction($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $action = App_Model_Action::first(array('id = ?' => (int) $id));

            if (NULL === $action) {
                echo self::ERROR_MESSAGE_2;
            } else {
                $action->approved = 2;

                if ($action->validate()) {
                    $action->save();

                    Event::fire('admin.log', array('success', 'Action id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Action id: ' . $id));
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

        if (RequestMethods::post('performActionAction')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/action/');
            }

            $ids = RequestMethods::post('actionids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $action = App_Model_Action::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $action) {
                        foreach ($action as $_report) {
                            if (!$_report->delete()) {
                                $errors[] = 'An error occured while deleting ' . $_report->getTitle();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'Action ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_6);
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/action/');

                    break;
                case 'activate':
                    $action = App_Model_Action::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $action) {
                        foreach ($action as $_report) {
                            $_report->active = true;

                            if ($_report->validate()) {
                                $_report->save();
                            } else {
                                $errors[] = "Action id {$_report->getId()} - {$_report->getTitle()} errors: "
                                        . join(', ', $_report->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'Action ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_4);
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/action/');

                    break;
                case 'deactivate':
                    $action = App_Model_Action::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $action) {
                        foreach ($action as $_report) {
                            $_report->active = false;

                            if ($_report->validate()) {
                                $_report->save();
                            } else {
                                $errors[] = "Action id {$_report->getId()} - {$_report->getTitle()} errors: "
                                        . join(', ', $_report->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'Action ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_5);
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/action/');
                    break;
                default:
                    self::redirect('/admin/action/');
                    break;
            }
        }
    }

}
