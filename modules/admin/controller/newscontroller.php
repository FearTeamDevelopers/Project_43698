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
class NewsController extends Controller
{

    private $_errors = array();

    /**
     * Check whether user has access to news or not
     * 
     * @param \App\Model\NewsModel $news
     * @return boolean
     */
    private function _checkAccess(\App\Model\NewsModel $news)
    {
        if ($this->_security->isGranted('role_admin') === true ||
                $news->getUserId() == $this->getUser()->getId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check whether news unique identifier already exist or not
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = \App\Model\NewsModel::first(array('urlKey = ?' => $key));

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Create and return new news object
     * 
     * @return \App\Model\NewsModel
     */
    private function _createObject()
    {
        $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

        if (!$this->_checkUrlKey($urlKey)) {
            $this->_errors['title'] = array('This title is already used');
        }

        $autoApprove = Registry::get('configuration')->news_autopublish;

        $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), 
                array('/novinky/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext'));

        $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

        $news = new \App\Model\NewsModel(array(
            'title' => RequestMethods::post('title'),
            'userId' => $this->getUser()->getId(),
            'userAlias' => $this->getUser()->getWholeName(),
            'urlKey' => $urlKey,
            'approved' => $autoApprove,
            'archive' => 0,
            'shortBody' => $shortText,
            'body' => RequestMethods::post('text'),
            'rank' => RequestMethods::post('rank', 1),
            'keywords' => $keywords,
            'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
            'metaDescription' => RequestMethods::post('metadescription')
        ));
        
        return $news;
    }

    /**
     * Edit existing news object
     * 
     * @param \App\Model\NewsModel $object
     * @return \App\Model\NewsModel
     */
    private function _editObject(\App\Model\NewsModel $object)
    {
        $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

        if ($object->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
            $this->_errors['title'] = array('This title is already used');
        }

        if (null === $object->userId) {
            $object->userId = $this->getUser()->getId();
            $object->userAlias = $this->getUser()->getWholeName();
        }

        $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), 
                array('/novinky/r/' . $urlKey, '[Celý článek]'), RequestMethods::post('shorttext'));

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
        
        return $object;
    }

    /**
     * Check if there is object used for preview saved in session
     * 
     * @return \App\Model\NewsModel
     */
    private function _checkForObject()
    {
        $session = Registry::get('session');
        $news = $session->get('newsPreview');
        $session->erase('newsPreview');

        return $news;
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
     * Create new news
     * 
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();

        $news = $this->_checkForObject();
        
        $view->set('news', $news)
            ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddNews')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/news/');
            }

            $news = $this->_createObject();

            if (empty($this->_errors) && $news->validate()) {
                $id = $news->save();
                $this->getCache()->invalidate();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage('News' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $this->_errors + $news->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('news', $news);
            }
        }
        
        if (RequestMethods::post('submitPreviewNews')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/news/');
            }

            $news = $this->_createObject();

            if (empty($this->_errors) && $news->validate()) {
                $session = Registry::get('session');
                $session->set('newsPreview', $news);
                
                self::redirect('/news/preview?action=add');
            } else {
                $view->set('errors', $this->_errors + $news->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('news', $news);
            }
        }
    }

    /**
     * Edit existing news
     * 
     * @before _secured, _participant
     * @param int   $id     news id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $news = $this->_checkForObject();
        
        if (null !== $news) {
            $view->set('news', $news);
        } else {
            $news = \App\Model\NewsModel::first(array('id = ?' => (int) $id));

            if (null === $news) {
                $view->warningMessage(self::ERROR_MESSAGE_2);
                $this->_willRenderActionView = false;
                self::redirect('/admin/news/');
            }

            if (!$this->_checkAccess($news)) {
                $view->warningMessage(self::ERROR_MESSAGE_4);
                $this->_willRenderActionView = false;
                self::redirect('/admin/news/');
            }

            $view->set('news', $news);
        }

        if (RequestMethods::post('submitEditNews')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/news/');
            }

            $news = $this->_editObject($news);

            if (empty($this->_errors) && $news->validate()) {
                $news->save();
                $this->getCache()->invalidate();
                
                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail', 'News id: ' . $id));
                $view->set('errors', $this->_errors + $news->getErrors());
            }
        }
        
        if (RequestMethods::post('submitPreviewNews')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/news/');
            }

            $action = $this->_editObject($news);

            if (empty($this->_errors) && $action->validate()) {
                $session = Registry::get('session');
                $session->set('newsPreview', $news);
                
                self::redirect('/news/preview?action=edit');
            } else {
                $view->set('errors', $this->_errors + $news->getErrors());
            }
        }
    }

    /**
     * Delete existing news
     * 
     * @before _secured, _participant
     * @param int   $id     news id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $news = \App\Model\NewsModel::first(
                        array('id = ?' => (int) $id), array('id', 'userId')
        );

        if (NULL === $news) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($this->_checkAccess($news)) {
                if ($news->delete()) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('success', 'News id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'News id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            } else {
                echo self::ERROR_MESSAGE_4;
            }
        }
    }

    /**
     * Approve new news
     * 
     * @before _secured, _admin
     * @param int   $id     news id
     */
    public function approveNews($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $news = \App\Model\NewsModel::first(array('id = ?' => (int) $id));

        if (NULL === $news) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $news->approved = 1;

            if (null === $news->userId) {
                $news->userId = $this->getUser()->getId();
                $news->userAlias = $this->getUser()->getWholeName();
            }

            if ($news->validate()) {
                $news->save();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'News id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * Reject new news
     * 
     * @before _secured, _admin
     * @param int   $id     news id
     */
    public function rejectNews($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $news = \App\Model\NewsModel::first(array('id = ?' => (int) $id));

        if (NULL === $news) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $news->approved = 2;

            if (null === $news->userId) {
                $news->userId = $this->getUser()->getId();
                $news->userAlias = $this->getUser()->getWholeName();
            }

            if ($news->validate()) {
                $news->save();

                Event::fire('admin.log', array('success', 'News id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'News id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * Return list of news to insert news link to content
     * 
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $news = \App\Model\NewsModel::all(array(), array('urlKey', 'title'));

        $view->set('news', $news);
    }

    /**
     * Execute basic operation over multiple news
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
                $news = \App\Model\NewsModel::all(array(
                            'id IN ?' => $ids
                ));
                if (NULL !== $news) {
                    foreach ($news as $_news) {
                        if (!$_news->delete()) {
                            $errors[] = 'An error occured while deleting ' . $_news->getTitle();
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('delete success', 'News ids: ' . join(',', $ids)));
                    echo self::SUCCESS_MESSAGE_6;
                } else {
                    Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'activate':
                $news = \App\Model\NewsModel::all(array(
                            'id IN ?' => $ids,
                            'active = ?' => false
                ));
                if (NULL !== $news) {
                    foreach ($news as $_news) {
                        $_news->active = true;

                        if (null === $_news->userId) {
                            $_news->userId = $this->getUser()->getId();
                            $_news->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($_news->validate()) {
                            $_news->save();
                        } else {
                            $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                    . join(', ', $_news->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('activate success', 'News ids: ' . join(',', $ids)));
                    echo self::SUCCESS_MESSAGE_4;
                } else {
                    Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'deactivate':
                $news = \App\Model\NewsModel::all(array(
                            'id IN ?' => $ids,
                            'active = ?' => true
                ));
                if (NULL !== $news) {
                    foreach ($news as $_news) {
                        $_news->active = false;

                        if (null === $_news->userId) {
                            $_news->userId = $this->getUser()->getId();
                            $_news->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($_news->validate()) {
                            $_news->save();
                        } else {
                            $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                    . join(', ', $_news->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->invalidate();
                    Event::fire('admin.log', array('deactivate success', 'News ids: ' . join(',', $ids)));
                    echo self::SUCCESS_MESSAGE_5;
                } else {
                    Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                    $message = join(PHP_EOL, $errors);
                    echo $message;
                }

                break;
            case 'approve':
                $news = \App\Model\NewsModel::all(array(
                            'id IN ?' => $ids,
                            'approved IN ?' => array(0, 2)
                ));

                if (NULL !== $news) {
                    foreach ($news as $_news) {
                        $_news->approved = 1;

                        if (null === $_news->userId) {
                            $_news->userId = $this->getUser()->getId();
                            $_news->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($_news->validate()) {
                            $_news->save();
                        } else {
                            $errors[] = "Action id {$_news->getId()} - {$_news->getTitle()} errors: "
                                    . join(', ', $_news->getErrors());
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
                $news = \App\Model\NewsModel::all(array(
                            'id IN ?' => $ids,
                            'approved IN ?' => array(0, 1)
                ));

                if (NULL !== $news) {
                    foreach ($news as $_news) {
                        $_news->approved = 2;

                        if (null === $_news->userId) {
                            $_news->userId = $this->getUser()->getId();
                            $_news->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($_news->validate()) {
                            $_news->save();
                        } else {
                            $errors[] = "Action id {$_news->getId()} - {$_news->getTitle()} errors: "
                                    . join(', ', $_news->getErrors());
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
                echo self::ERROR_MESSAGE_2;
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
            $whereCond = "nw.created LIKE '%%?%%' OR nw.userAlias LIKE '%%?%%' OR nw.title LIKE '%%?%%'";

            $query = \App\Model\NewsModel::getQuery(
                            array('nw.id', 'nw.userId', 'nw.userAlias', 'nw.title',
                                'nw.active', 'nw.approved', 'nw.archive', 'nw.created'))
                    ->join('tb_user', 'nw.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                    ->wheresql($whereCond, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('nw.id', $dir);
                } elseif ($column == 1) {
                    $query->order('nw.title', $dir);
                } elseif ($column == 2) {
                    $query->order('nw.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('nw.created', $dir);
                }
            } else {
                $query->order('nw.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $news = \App\Model\NewsModel::initialize($query);

            $countQuery = \App\Model\NewsModel::getQuery(array('nw.id'))
                    ->join('tb_user', 'nw.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                    ->wheresql($whereCond, $search, $search, $search);

            $newsCount = \App\Model\NewsModel::initialize($countQuery);
            unset($countQuery);
            $count = count($newsCount);
            unset($newsCount);
        } else {
            $query = \App\Model\NewsModel::getQuery(
                            array('nw.id', 'nw.userId', 'nw.userAlias', 'nw.title',
                                'nw.active', 'nw.approved', 'nw.archive', 'nw.created'))
                    ->join('tb_user', 'nw.userId = us.id', 'us', array('us.firstname', 'us.lastname'));

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('nw.id', $dir);
                } elseif ($column == 1) {
                    $query->order('nw.title', $dir);
                } elseif ($column == 2) {
                    $query->order('nw.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('nw.created', $dir);
                }
            } else {
                $query->order('nw.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $news = \App\Model\NewsModel::initialize($query);

            $count = \App\Model\NewsModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = array();
        if (null !== $news) {
            foreach ($news as $_news) {
                $label = '';
                if ($_news->active) {
                    $label .= "<span class='labelProduct labelProductGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='labelProduct labelProductRed'>Neaktivní</span>";
                }

                if ($_news->approved == 1) {
                    $label .= "<span class='labelProduct labelProductGreen'>Schváleno</span>";
                } elseif ($_news->approved == 2) {
                    $label .= "<span class='labelProduct labelProductRed'>Zamítnuto</span>";
                } else {
                    $label .= "<span class='labelProduct labelProductOrange'>Čeká na schválení</span>";
                }

                if ($this->getUser()->getId() == $_news->getUserId()) {
                    $label .= "<span class='labelProduct labelProductGray'>Moje</span>";
                }

                if ($_news->archive) {
                    $archiveLabel = "<span class='labelProduct labelProductGreen'>Ano</span>";
                } else {
                    $archiveLabel = "<span class='labelProduct labelProductGray'>Ne</span>";
                }

                $arr = array();
                $arr [] = "[ \"" . $_news->getId() . "\"";
                $arr [] = "\"" . htmlentities($_news->getTitle()) . "\"";
                $arr [] = "\"" . $_news->getUserAlias() . "\"";
                $arr [] = "\"" . $_news->getCreated() . "\"";
                $arr [] = "\"" . $label . "\"";
                $arr [] = "\"" . $archiveLabel . "\"";

                $tempStr = "\"<a href='/admin/news/edit/" . $_news->id . "' class='btn btn3 btn_pencil' title='Upravit'></a>";

                if ($this->isAdmin() || $_news->userId == $this->getUser()->getId()) {
                    $tempStr .= "<a href='/admin/news/delete/" . $_news->id . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }

                if ($this->isAdmin() && $_news->approved == 0) {
                    $tempStr .= "<a href='/admin/news/approvenews/" . $_news->id . "' class='btn btn3 btn_info ajaxReload' title='Schválit'></a>";
                    $tempStr .= "<a href='/admin/news/rejectnews/" . $_news->id . "' class='btn btn3 btn_stop ajaxReload' title='Zamítnout'></a>";
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
