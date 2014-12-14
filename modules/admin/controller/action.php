<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

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

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

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

            $autoApprove = Registry::get('configuration')->action_autopublish;

            $shortText = str_replace(array('(!read_more_link!)','(!read_more_title!)'),
                    array('/akce/r/'.$urlKey, '[Celý článek]'), 
                    RequestMethods::post('shorttext')
            );
            
            $action = new App_Model_Action(array(
                'title' => RequestMethods::post('title'),
                'userId' => $this->getUser()->getId(),
                'userAlias' => $this->getUser()->getWholeName(),
                'urlKey' => $urlKey,
                'approved' => $autoApprove,
                'archive' => 0,
                'shortBody' => $shortText,
                'body' => RequestMethods::post('text'),
                'expirationDate' => RequestMethods::post('expiration'),
                'rank' => RequestMethods::post('rank', 1),
                'keywords' => RequestMethods::post('keywords')
            ));

            if (empty($errors) && $action->validate()) {
                $id = $action->save();
                Registry::get('cache')->invalidate();

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

        $view->set('action', $action);

        if (RequestMethods::post('submitEditAction')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/action/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($action->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            if ($action->userId === null) {
                $action->userId = $this->getUser()->getId();
                $action->userAlias = $this->getUser()->getWholeName();
            }
            
            $shortText = str_replace(array('(!read_more_link!)','(!read_more_title!)'),
                    array('/akce/r/'.$urlKey, '[Celý článek]'), 
                    RequestMethods::post('shorttext')
            );

            $action->title = RequestMethods::post('title');
            $action->urlKey = $urlKey;
            $action->expirationDate = RequestMethods::post('expiration');
            $action->body = RequestMethods::post('text');
            $action->shortBody = $shortText;
            $action->rank = RequestMethods::post('rank', 1);
            $action->active = RequestMethods::post('active');
            $action->approved = RequestMethods::post('approve');
            $action->archive = RequestMethods::post('archive');
            $action->keywords = RequestMethods::post('keywords');

            if (empty($errors) && $action->validate()) {
                $action->save();
                Registry::get('cache')->invalidate();

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

        $action = App_Model_Action::first(
                        array('id = ?' => (int) $id), array('id', 'userId')
        );

        if (NULL === $action) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($this->_security->isGranted('role_admin') === true ||
                    $action->getUserId() == $this->getUser()->getId()) {
                if ($action->delete()) {
                    Registry::get('cache')->invalidate();
                    Event::fire('admin.log', array('success', 'Action id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Action id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            } else {
                echo self::ERROR_MESSAGE_4;
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function approveAction($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $action = App_Model_Action::first(array('id = ?' => (int) $id));

        if (NULL === $action) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $action->approved = 1;

            if ($action->userId === null) {
                $action->userId = $this->getUser()->getId();
                $action->userAlias = $this->getUser()->getWholeName();
            }

            if ($action->validate()) {
                $action->save();

                Event::fire('admin.log', array('success', 'Action id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Action id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function rejectAction($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $action = App_Model_Action::first(array('id = ?' => (int) $id));

        if (NULL === $action) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $action->approved = 2;

            if ($action->userId === null) {
                $action->userId = $this->getUser()->getId();
                $action->userAlias = $this->getUser()->getWholeName();
            }

            if ($action->validate()) {
                $action->save();

                Event::fire('admin.log', array('success', 'Action id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Action id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;
        
        $actions = App_Model_Action::all(
                array('approved = ?' => 1, 'active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s'))
        );
        
        $view->set('actions', $actions);
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
                        foreach ($action as $_action) {
                            if (!$_action->delete()) {
                                $errors[] = 'An error occured while deleting ' . $_action->getTitle();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'Action ids: ' . join(',', $ids)));
                        Registry::get('cache')->invalidate();
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
                        foreach ($action as $_action) {
                            $_action->active = true;

                            if ($_action->userId === null) {
                                $_action->userId = $this->getUser()->getId();
                                $_action->userAlias = $this->getUser()->getWholeName();
                            }

                            if ($_action->validate()) {
                                $_action->save();
                            } else {
                                $errors[] = "Action id {$_action->getId()} - {$_action->getTitle()} errors: "
                                        . join(', ', $_action->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'Action ids: ' . join(',', $ids)));
                        Registry::get('cache')->invalidate();
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
                        foreach ($action as $_action) {
                            $_action->active = false;

                            if ($_action->userId === null) {
                                $_action->userId = $this->getUser()->getId();
                                $_action->userAlias = $this->getUser()->getWholeName();
                            }

                            if ($_action->validate()) {
                                $_action->save();
                            } else {
                                $errors[] = "Action id {$_action->getId()} - {$_action->getTitle()} errors: "
                                        . join(', ', $_action->getErrors());
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'Action ids: ' . join(',', $ids)));
                        Registry::get('cache')->invalidate();
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
