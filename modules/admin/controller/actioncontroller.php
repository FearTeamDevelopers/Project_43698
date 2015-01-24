<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class ActionController extends Controller
{

    private $_errors = array();
    
    /**
     * Check whether user has access to action or not
     * 
     * @param \App\Model\ActionModel $action
     * @return boolean
     */
    private function _checkAccess(\App\Model\ActionModel $action)
    {
        if($this->_security->isGranted('role_admin') === true ||
                $action->getUserId() == $this->getUser()->getId()){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * Check whether action unique identifier already exist or not
     * 
     * @param string $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = \App\Model\ActionModel::first(array('urlKey = ?' => $key));

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Create and return new action object
     * 
     * @return \App\Model\ActionModel
     */
    private function _createObject()
    {
        $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

        if (!$this->_checkUrlKey($urlKey)) {
            $this->_errors['title'] = array('This title is already used');
        }

        $autoApprove = Registry::get('configuration')->action_autopublish;

        $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), 
                array('/akce/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext')
        );

        $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

        $action = new \App\Model\ActionModel(array(
            'title' => RequestMethods::post('title'),
            'userId' => $this->getUser()->getId(),
            'userAlias' => $this->getUser()->getWholeName(),
            'urlKey' => $urlKey,
            'approved' => $autoApprove,
            'archive' => 0,
            'shortBody' => $shortText,
            'body' => RequestMethods::post('text'),
            'rank' => RequestMethods::post('rank', 1),
            'startDate' => RequestMethods::post('datestart'),
            'endDate' => RequestMethods::post('dateend'),
            'startTime' => RequestMethods::post('timestart'),
            'endTime' => RequestMethods::post('timeend'),
            'keywords' => $keywords,
            'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
            'metaDescription' => RequestMethods::post('metadescription')
        ));

        return $action;
    }
    
    /**
     * Edit existing action object
     * 
     * @param \App\Model\ActionModel $object
     * @return \App\Model\ActionModel
     */
    private function _editObject(\App\Model\ActionModel $object)
    {
        $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

        if ($object->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
            $this->_errors['title'] = array('This title is already used');
        }

        if (null === $object->userId) {
            $object->userId = $this->getUser()->getId();
            $object->userAlias = $this->getUser()->getWholeName();
        }

        $shortText = str_replace(
                array('(!read_more_link!)', '(!read_more_title!)'), 
                array('/akce/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext')
        );

        $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

        $object->title = RequestMethods::post('title');
        $object->urlKey = $urlKey;
        $object->body = RequestMethods::post('text');
        $object->shortBody = $shortText;
        $object->rank = RequestMethods::post('rank', 1);
        $object->startDate = RequestMethods::post('datestart');
        $object->endDate = RequestMethods::post('dateend');
        $object->startTime = RequestMethods::post('timestart');
        $object->endTime = RequestMethods::post('timeend');
        $object->active = RequestMethods::post('active');
        $object->approved = RequestMethods::post('approve');
        $object->archive = RequestMethods::post('archive');
        $object->keywords = $keywords;
        $object->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
        $object->metaDescription = RequestMethods::post('metadescription');

        return $object;
    }

    /**
     * Check if there is object used for preview saved in session
     * 
     * @return \App\Model\ActionModel
     */
    private function _checkForObject()
    {
        $session = Registry::get('session');
        $action = $session->get('actionPreview');
        $session->erase('actionPreview');
        
        return $action;
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
     * Create new action
     * 
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();
        
        $action = $this->_checkForObject();
        
        $view->set('action', $action)
            ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddAction')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/action/');
            }

            $action = $this->_createObject();

            if (empty($this->_errors) && $action->validate()) {
                $id = $action->save();
                $this->getCache()->invalidate();

                Event::fire('admin.log', array('success', 'Action id: ' . $id));
                $view->successMessage('Action' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/action/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $this->_errors + $action->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('action', $action);
            }
        }
        
        if (RequestMethods::post('submitPreviewAction')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/action/');
            }

            $action = $this->_createObject();

            if (empty($this->_errors) && $action->validate()) {
                $session = Registry::get('session');
                $session->set('actionPreview', $action);
                
                self::redirect('/action/preview?action=add');
            } else {
                $view->set('errors', $this->_errors + $action->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('action', $action);
            }
        }
    }

    /**
     * Edit existing action
     * 
     * @before _secured, _participant
     * @param int   $id     action id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $action = $this->_checkForObject();

        if (null !== $action) {
            $view->set('action', $action);
        } else {

            $action = \App\Model\ActionModel::first(array('id = ?' => (int) $id));

            if (null === $action) {
                $view->warningMessage(self::ERROR_MESSAGE_2);
                $this->_willRenderActionView = false;
                self::redirect('/admin/action/');
            }

            if (!$this->_checkAccess($action)) {
                $view->warningMessage(self::ERROR_MESSAGE_4);
                $this->_willRenderActionView = false;
                self::redirect('/admin/action/');
            }

            $view->set('action', $action);
        }

        if (RequestMethods::post('submitEditAction')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/action/');
            }

            $action = $this->_editObject($action);

            if (empty($this->_errors) && $action->validate()) {
                $action->save();
                $this->getCache()->invalidate();

                Event::fire('admin.log', array('success', 'Action id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/action/');
            } else {
                Event::fire('admin.log', array('fail', 'Action id: ' . $id));
                $view->set('errors', $this->_errors + $action->getErrors());
            }
        }
        
        if (RequestMethods::post('submitPreviewAction')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/action/');
            }

            $action = $this->_editObject($action);

            if (empty($this->_errors) && $action->validate()) {
                $session = Registry::get('session');
                $session->set('actionPreview', $action);
                
                self::redirect('/action/preview?action=edit');
            } else {
                $view->set('errors', $this->_errors + $action->getErrors());
            }
        }
    }

    /**
     * Delete existing action
     * 
     * @before _secured, _participant
     * @param int   $id     action id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $action = \App\Model\ActionModel::first(
                        array('id = ?' => (int) $id), array('id', 'userId')
        );

        if (NULL === $action) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($this->_checkAccess($action)) {
                if ($action->delete()) {
                    $this->getCache()->invalidate();
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
     * Approve new action
     * 
     * @before _secured, _admin
     * @param int   $id     action id
     */
    public function approveAction($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $action = \App\Model\ActionModel::first(array('id = ?' => (int) $id));

        if (NULL === $action) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $action->approved = 1;

            if (null === $action->userId) {
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
     * Reject new action
     * 
     * @before _secured, _admin
     * @param int   $id     action id
     */
    public function rejectAction($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $action = \App\Model\ActionModel::first(array('id = ?' => (int) $id));

        if (NULL === $action) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $action->approved = 2;

            if (null === $action->userId) {
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
     * Return list of actions to insert action link to content
     * 
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $actions = \App\Model\ActionModel::all(array(), array('urlKey', 'title'));

        $view->set('actions', $actions);
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
                $actions = \App\Model\ActionModel::all(
                        array('id IN ?' => $ids), 
                        array('id','title')
                );
                
                if (NULL !== $actions) {
                    foreach ($actions as $action) {
                        if (!$action->delete()) {
                            $errors[] = 'An error occured while deleting ' . $action->getTitle();
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('delete success', 'Action ids: ' . join(',', $ids)));
                    $this->getCache()->invalidate();
                    echo self::SUCCESS_MESSAGE_6;
                } else {
                    Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'activate':
                $actions = \App\Model\ActionModel::all(array(
                            'id IN ?' => $ids,
                            'active = ?' => false
                ));
                
                if (NULL !== $actions) {
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
                                    . join(', ', $action->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('activate success', 'Action ids: ' . join(',', $ids)));
                    $this->getCache()->invalidate();
                    echo self::SUCCESS_MESSAGE_4;
                } else {
                    Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'deactivate':
                $actions = \App\Model\ActionModel::all(array(
                            'id IN ?' => $ids,
                            'active = ?' => true
                ));
                
                if (NULL !== $actions) {
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
                                    . join(', ', $action->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    Event::fire('admin.log', array('deactivate success', 'Action ids: ' . join(',', $ids)));
                    $this->getCache()->invalidate();
                    echo self::SUCCESS_MESSAGE_5;
                } else {
                    Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'approve':
                $actions = \App\Model\ActionModel::all(array(
                            'id IN ?' => $ids,
                            'approved IN ?' => array(0,2)
                ));
                
                if (NULL !== $actions) {
                    foreach ($actions as $action) {
                        $action->approved = 1;

                        if (null === $action->userId) {
                            $action->userId = $this->getUser()->getId();
                            $action->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($action->validate()) {
                            $action->save();
                        } else {
                            $errors[] = "Action id {$action->getId()} - {$action->getTitle()} errors: "
                                    . join(', ', $action->getErrors());
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
                $actions = \App\Model\ActionModel::all(array(
                            'id IN ?' => $ids,
                            'approved IN ?' => array(0,1)
                ));
                
                if (NULL !== $actions) {
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
                                    . join(', ', $action->getErrors());
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
            $whereCond = "ac.created LIKE '%%?%%' OR ac.userAlias LIKE '%%?%%' OR ac.title LIKE '%%?%%'";

            $query = \App\Model\ActionModel::getQuery(
                            array('ac.id', 'ac.userId', 'ac.userAlias', 'ac.title', 
                                'ac.active', 'ac.approved', 'ac.archive', 'ac.created'))
                    ->join('tb_user', 'ac.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
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
                }
            } else {
                $query->order('ac.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $actions = \App\Model\ActionModel::initialize($query);

            $countQuery = \App\Model\ActionModel::getQuery(array('ac.id'))
                    ->join('tb_user', 'ac.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                    ->wheresql($whereCond, $search, $search, $search);

            $actionsCount = \App\Model\ActionModel::initialize($countQuery);
            unset($countQuery);
            $count = count($actionsCount);
            unset($actionsCount);
        } else {
            $query = \App\Model\ActionModel::getQuery(
                            array('ac.id', 'ac.userId', 'ac.userAlias', 'ac.title', 
                                'ac.active', 'ac.approved', 'ac.archive', 'ac.created'))
                    ->join('tb_user', 'ac.userId = us.id', 'us', array('us.firstname', 'us.lastname'));

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
                }
            } else {
                $query->order('ac.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $actions = \App\Model\ActionModel::initialize($query);

            $count = \App\Model\ActionModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = array();
        if (null !== $actions) {
            foreach ($actions as $action) {
                $label = '';
                if ($action->active) {
                    $label .= "<span class='labelProduct labelProductGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='labelProduct labelProductRed'>Neaktivní</span>";
                }

                if ($action->approved == 1) {
                    $label .= "<span class='labelProduct labelProductGreen'>Schváleno</span>";
                } elseif ($action->approved == 2) {
                    $label .= "<span class='labelProduct labelProductRed'>Zamítnuto</span>";
                } else {
                    $label .= "<span class='labelProduct labelProductOrange'>Čeká na schválení</span>";
                }

                if($this->getUser()->getId() == $action->getUserId()){
                    $label .= "<span class='labelProduct labelProductGray'>Moje</span>";
                }

                if ($action->archive) {
                    $archiveLabel = "<span class='labelProduct labelProductGreen'>Ano</span>";
                } else {
                    $archiveLabel = "<span class='labelProduct labelProductGray'>Ne</span>";
                }

                $arr = array();
                $arr [] = "[ \"" . $action->getId() . "\"";
                $arr [] = "\"" . htmlentities($action->getTitle()) . "\"";
                $arr [] = "\"" . $action->getUserAlias() . "\"";
                $arr [] = "\"" . $action->getCreated() . "\"";
                $arr [] = "\"" . $label . "\"";
                $arr [] = "\"" . $archiveLabel . "\"";

                $tempStr = "\"<a href='/admin/action/edit/" . $action->id . "' class='btn btn3 btn_pencil' title='Upravit'></a>";

                if ($this->isAdmin() || $action->userId == $this->getUser()->getId()) {
                    $tempStr .= "<a href='/admin/action/delete/" . $action->id . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }

                if ($this->isAdmin() && $action->approved == 0) {
                    $tempStr .= "<a href='/admin/action/approveaction/" . $action->id . "' class='btn btn3 btn_info ajaxReload' title='Schválit'></a>";
                    $tempStr .= "<a href='/admin/action/rejectaction/" . $action->id . "' class='btn btn3 btn_stop ajaxReload' title='Zamítnout'></a>";
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

}