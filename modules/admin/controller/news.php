<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_News extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_News::first(array('urlKey = ?' => $key));

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

        $news = App_Model_News::all();

        $view->set('news', $news);
    }

    /**
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddNews')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/news/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            $autoApprove = Registry::get('configuration')->news_autopublish;

            $shortText = str_replace(array('(!read_more_link!)','(!read_more_title!)'),
                    array('/novinky/r/'.$urlKey, '[Celý článek]'), 
                    RequestMethods::post('shorttext')
            );
            
            $news = new App_Model_News(array(
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
                'keywords' => RequestMethods::post('keywords'),
                'metaTitle' => RequestMethods::post('metatitle', RequestMethods::post('title')),
                'metaDescription' => RequestMethods::post('metadescription')
            ));

            if (empty($errors) && $news->validate()) {
                $id = $news->save();

                Registry::get('cache')->invalidate();
                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage('News' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors + $news->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('news', $news);
            }
        }
    }

    /**
     * @before _secured, _participant
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $news = App_Model_News::first(array('id = ?' => (int) $id));

        if ($news === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/news/');
        }

        if ($this->_security->isGranted('role_admin') !== true ||
                $news->getUserId() !== $this->getUser()->getId()) {
            $view->warningMessage(self::ERROR_MESSAGE_4);
            self::redirect('/admin/news/');
        }

        $view->set('news', $news);

        if (RequestMethods::post('submitEditNews')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/news/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($news->urlKey != $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('This title is already used');
            }

            if ($news->userId === null) {
                $news->userId = $this->getUser()->getId();
                $news->userAlias = $this->getUser()->getWholeName();
            }
            
            $shortText = str_replace(array('(!read_more_link!)','(!read_more_title!)'),
                    array('/novinky/r/'.$urlKey, '[Celý článek]'), 
                    RequestMethods::post('shorttext')
            );

            $news->title = RequestMethods::post('title');
            $news->urlKey = $urlKey;
            $news->expirationDate = RequestMethods::post('expiration');
            $news->body = RequestMethods::post('text');
            $news->shortBody = $shortText;
            $news->rank = RequestMethods::post('rank', 1);
            $news->active = RequestMethods::post('active');
            $news->approved = RequestMethods::post('approve');
            $news->archive = RequestMethods::post('archive');
            $news->keywords = RequestMethods::post('keywords');
            $news->metaTitle = RequestMethods::post('metatitle', RequestMethods::post('title'));
            $news->metaDescription = RequestMethods::post('metadescription');

            if (empty($errors) && $news->validate()) {
                $news->save();

                Registry::get('cache')->invalidate();
                Event::fire('admin.log', array('success', 'News id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', array('fail', 'News id: ' . $id));
                $view->set('errors', $errors + $news->getErrors());
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

        $news = App_Model_News::first(
                        array('id = ?' => (int) $id), array('id', 'userId')
        );

        if (NULL === $news) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($this->_security->isGranted('role_admin') === true ||
                    $news->getUserId() == $this->getUser()->getId()) {
                if ($news->delete()) {
                    Registry::get('cache')->invalidate();
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
     * @before _secured, _admin
     */
    public function approveNews($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $news = App_Model_News::first(array('id = ?' => (int) $id));

        if (NULL === $news) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $news->approved = 1;

            if ($news->userId === null) {
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
     * @before _secured, _admin
     */
    public function rejectNews($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $news = App_Model_News::first(array('id = ?' => (int) $id));

        if (NULL === $news) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $news->approved = 2;

            if ($news->userId === null) {
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
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;
        
        $news = App_Model_News::all(
                array('approved = ?' => 1, 'active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s'))
        );
        
        $view->set('news', $news);
    }

    /**
     * @before _secured, _admin
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performNewsAction')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/news/');
            }

            $ids = RequestMethods::post('newsids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $news = App_Model_News::all(array(
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
                        Registry::get('cache')->invalidate();
                        Event::fire('admin.log', array('delete success', 'News ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_6);
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');

                    break;
                case 'activate':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $news) {
                        foreach ($news as $_news) {
                            $_news->active = true;

                            if ($_news->userId === null) {
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
                        Registry::get('cache')->invalidate();
                        Event::fire('admin.log', array('activate success', 'News ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_4);
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');

                    break;
                case 'deactivate':
                    $news = App_Model_News::all(array(
                                'id IN ?' => $ids
                    ));
                    if (NULL !== $news) {
                        foreach ($news as $_news) {
                            $_news->active = false;

                            if ($_news->userId === null) {
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
                        Registry::get('cache')->invalidate();
                        Event::fire('admin.log', array('deactivate success', 'News ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_5);
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join(PHP_EOL, $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/news/');
                    break;
                default:
                    self::redirect('/admin/news/');
                    break;
            }
        }
    }

}
