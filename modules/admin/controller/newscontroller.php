<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use Admin\Model\Notifications\Email\News as NewsNotification;
use App\Model\NewsModel;

/**
 *
 */
class NewsController extends Controller
{

    /**
     * Check whether user has access to news or not.
     *
     * @param NewsModel $news
     *
     * @return bool
     */
    private function checkAccess(NewsModel $news)
    {
        if ($this->isAdmin() === true ||
                $news->getUserId() == $this->getUser()->getId()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if there is object used for preview saved in session.
     *
     * @return NewsModel
     */
    private function checkForObject()
    {
        $session = Registry::get('session');
        $news = $session->get('newsPreview');
        $session->remove('newsPreview');

        return $news;
    }

    /**
     * Get list of all news. Loaded via datatables ajax.
     * For more check load function.
     *
     * @before _secured, _participant
     */
    public function index()
    {

    }

    /**
     * Create new news.
     *
     * @before _secured, _participant
     */
    public function add()
    {
        $view = $this->getActionView();
        $news = $this->checkForObject();

        $newsConcepts = \Admin\Model\ConceptModel::all([
                    'userId = ?' => $this->getUser()->getId(),
                    'type = ?' => \Admin\Model\ConceptModel::CONCEPT_TYPE_NEWS,], ['id', 'created', 'modified'], ['created' => 'DESC'], 10);

        $view->set('news', $news)
                ->set('concepts', $newsConcepts);

        if (RequestMethods::post('submitAddNews')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/news/');
            }

            list($news, $errors) = NewsModel::createFromPost(
                            RequestMethods::getPostDataBag(), ['user' => $this->getUser(), 'autoPublish' => $this->getConfig()->news_autopublish]
            );

            if (empty($errors) && $news->validate()) {
                $id = $news->save();
                
//                NewsNotification::getInstance()->onCreate($news);
                
                $this->getCache()->erase('news');
                \Admin\Model\ConceptModel::deleteAll(['id = ?' => RequestMethods::post('conceptid')]);

                Event::fire('admin.log', ['success', 'News id: ' . $id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', ['fail',
                    'Errors: ' . json_encode($errors + $news->getErrors()),]);
                $view->set('errors', $errors + $news->getErrors())
                        ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                        ->set('news', $news)
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }

        if (RequestMethods::post('submitPreviewNews')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/news/');
            }

            list($news, $errors) = NewsModel::createFromPost(
                            RequestMethods::getPostDataBag(), ['user' => $this->getUser(), 'autoPublish' => $this->getConfig()->news_autopublish]
            );

            if (empty($errors) && $news->validate()) {
                $session = Registry::get('session');
                $session->set('newsPreview', $news);
                \Admin\Model\ConceptModel::deleteAll(['id = ?' => RequestMethods::post('conceptid')]);

                self::redirect('/news/preview?action=add');
            } else {
                $view->set('errors', $errors + $news->getErrors())
                        ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                        ->set('news', $news)
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }
    }

    /**
     * Edit existing news.
     *
     * @before _secured, _participant
     *
     * @param int $id news id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $news = $this->checkForObject();

        if (null === $news) {
            $news = NewsModel::first(['id = ?' => (int) $id]);

            if (null === $news) {
                $view->warningMessage($this->lang('NOT_FOUND'));
                $this->willRenderActionView = false;
                self::redirect('/admin/news/');
            }

            if (!$this->checkAccess($news)) {
                $view->warningMessage($this->lang('LOW_PERMISSIONS'));
                $this->willRenderActionView = false;
                self::redirect('/admin/news/');
            }
        }

        $newsConcepts = \Admin\Model\ConceptModel::all([
                    'userId = ?' => $this->getUser()->getId(),
                    'type = ?' => \Admin\Model\ConceptModel::CONCEPT_TYPE_NEWS,], ['id', 'created', 'modified'], ['created' => 'DESC'], 10);

        $view->set('news', $news)
                ->set('concepts', $newsConcepts);

        if (RequestMethods::post('submitEditNews')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/news/');
            }

            $originalNews = clone $news;

            list($news, $errors) = NewsModel::editFromPost(
                            RequestMethods::getPostDataBag(), $news, [
                        'user' => $this->getUser(),
                        'isAdmin' => $this->isAdmin(),
                        'autoPublish' => $this->getConfig()->news_autopublish
                            ]
            );

            if (empty($errors) && $news->validate()) {
                $news->save();
                
//                NewsNotification::getInstance()->onUpdate($news);
                
                \Admin\Model\NewsHistoryModel::logChanges($originalNews, $news);
                $this->getCache()->erase('news');
                \Admin\Model\ConceptModel::deleteAll(['id = ?' => RequestMethods::post('conceptid')]);

                Event::fire('admin.log', ['success', 'News id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/news/');
            } else {
                Event::fire('admin.log', ['fail', 'News id: ' . $id,
                    'Errors: ' . json_encode($errors + $news->getErrors()),]);
                $view->set('errors', $errors + $news->getErrors())
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }

        if (RequestMethods::post('submitPreviewNews')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/news/');
            }

            list($news, $errors) = NewsModel::editFromPost(
                            RequestMethods::getPostDataBag(), $news, [
                        'user' => $this->getUser(),
                        'isAdmin' => $this->isAdmin(),
                        'autoPublish' => $this->getConfig()->news_autopublish
                            ]
            );

            if (empty($errors) && $news->validate()) {
                $session = Registry::get('session');
                $session->set('newsPreview', $news);

                self::redirect('/news/preview?action=edit');
            } else {
                $view->set('errors', $errors + $news->getErrors())
                        ->set('conceptid', RequestMethods::post('conceptid'));
            }
        }
    }

    /**
     * Delete existing news.
     *
     * @before _secured, _participant
     *
     * @param int $id news id
     */
    public function delete($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $news = NewsModel::first(
                        ['id = ?' => (int) $id], ['id', 'userId']
        );

        if (null === $news) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($this->checkAccess($news)) {
                if ($news->delete()) {
//                    NewsNotification::getInstance()->onDelete($news);
                    
                    $this->getCache()->erase('news');
                    Event::fire('admin.log', ['success', 'News id: ' . $id]);
                    $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['fail', 'News id: ' . $id]);
                    $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
                }
            } else {
                $this->ajaxResponse($this->lang('LOW_PERMISSIONS'), true, 401);
            }
        }
    }

    /**
     * Approve new news.
     *
     * @before _secured, _admin
     *
     * @param int $id news id
     */
    public function approveNews($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $news = NewsModel::first(['id = ?' => (int) $id]);

        if (null === $news) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $news->approved = 1;

            if (null === $news->userId) {
                $news->userId = $this->getUser()->getId();
                $news->userAlias = $this->getUser()->getWholeName();
            }

            if ($news->validate()) {
                $news->save();
                
//                NewsNotification::getInstance()->onCreate($news);
                
                $this->getCache()->erase('news');

                Event::fire('admin.log', ['success', 'News id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'News id: ' . $id,
                    'Errors: ' . json_encode($news->getErrors()),]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Reject new news.
     *
     * @before _secured, _admin
     *
     * @param int $id news id
     */
    public function rejectNews($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $news = NewsModel::first(['id = ?' => (int) $id]);

        if (null === $news) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $news->approved = 2;

            if (null === $news->userId) {
                $news->userId = $this->getUser()->getId();
                $news->userAlias = $this->getUser()->getWholeName();
            }

            if ($news->validate()) {
                $news->save();

                Event::fire('admin.log', ['success', 'News id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'News id: ' . $id,
                    'Errors: ' . json_encode($news->getErrors()),]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Return list of news to insert news link to content.
     *
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $news = NewsModel::all([], ['urlKey', 'title']);

        $view->set('news', $news);
    }

    /**
     * Execute basic operation over multiple news.
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
                $news = NewsModel::all([
                            'id IN ?' => $ids,
                ]);
                if (null !== $news) {
                    foreach ($news as $_news) {
                        if ($_news->delete()) {
//                            NewsNotification::getInstance()->onDelete($news);
                        } else {
                            $errors[] = $this->lang('DELETE_FAIL') . ' - ' . $_news->getTitle();
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->erase('news');
                    Event::fire('admin.log', ['delete news success', 'News ids: ' . implode(',', $ids)]);
                    $this->ajaxResponse($this->lang('DELETE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['delete news fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'activate':
                $news = NewsModel::all([
                            'id IN ?' => $ids,
                            'active = ?' => false,
                ]);
                if (null !== $news) {
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
                                    . implode(', ', $_news->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->erase('news');
                    Event::fire('admin.log', ['activate news success', 'News ids: ' . implode(',', $ids)]);
                    $this->ajaxResponse($this->lang('ACTIVATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['activate news fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'deactivate':
                $news = NewsModel::all([
                            'id IN ?' => $ids,
                            'active = ?' => true,
                ]);
                if (null !== $news) {
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
                                    . implode(', ', $_news->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->erase('news');
                    Event::fire('admin.log', ['deactivate news success', 'News ids: ' . implode(',', $ids)]);
                    $this->ajaxResponse($this->lang('DEACTIVATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['deactivate news fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'approve':
                $news = NewsModel::all([
                            'id IN ?' => $ids,
                            'approved IN ?' => [0, 2],
                ]);

                if (null !== $news) {
                    foreach ($news as $_news) {
                        $_news->approved = 1;

                        if (null === $_news->userId) {
                            $_news->userId = $this->getUser()->getId();
                            $_news->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($_news->validate()) {
                            $_news->save();
//                            NewsNotification::getInstance()->onCreate($news);
                        } else {
                            $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                    . implode(', ', $_news->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->erase('news');
                    Event::fire('admin.log', ['approve news success', 'News ids: ' . implode(',', $ids)]);
                    $this->ajaxResponse($this->lang('UPDATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['approve news fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            case 'reject':
                $news = NewsModel::all([
                            'id IN ?' => $ids,
                            'approved IN ?' => [0, 1],
                ]);

                if (null !== $news) {
                    foreach ($news as $_news) {
                        $_news->approved = 2;

                        if (null === $_news->userId) {
                            $_news->userId = $this->getUser()->getId();
                            $_news->userAlias = $this->getUser()->getWholeName();
                        }

                        if ($_news->validate()) {
                            $_news->save();
                        } else {
                            $errors[] = "News id {$_news->getId()} - {$_news->getTitle()} errors: "
                                    . implode(', ', $_news->getErrors());
                        }
                    }
                }

                if (empty($errors)) {
                    $this->getCache()->erase('news');
                    Event::fire('admin.log', ['reject news success', 'News ids: ' . implode(',', $ids)]);
                    $this->ajaxResponse($this->lang('UPDATE_SUCCESS'));
                } else {
                    Event::fire('admin.log', ['reject news fail', 'Errors:' . json_encode($errors)]);
                    $message = implode(PHP_EOL, $errors);
                    $this->ajaxResponse($message, true);
                }

                break;
            default:
                $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
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

        $page = (int) RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "nw.created LIKE '%%?%%' OR nw.userAlias LIKE '%%?%%' OR nw.title LIKE '%%?%%'";

            $query = NewsModel::getQuery(
                            ['nw.id', 'nw.userId', 'nw.userAlias', 'nw.title',
                                'nw.active', 'nw.approved', 'nw.archive', 'nw.created', 'nw.modified'])
                    ->join('tb_user', 'nw.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
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
                } elseif ($column == 4) {
                    $query->order('nw.modified', $dir);
                }
            } else {
                $query->order('nw.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $news = NewsModel::initialize($query);

            $countQuery = NewsModel::getQuery(['nw.id'])
                    ->join('tb_user', 'nw.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                    ->wheresql($whereCond, $search, $search, $search);

            $newsCount = NewsModel::initialize($countQuery);
            unset($countQuery);
            $count = count($newsCount);
            unset($newsCount);
        } else {
            $query = NewsModel::getQuery(
                            ['nw.id', 'nw.userId', 'nw.userAlias', 'nw.title',
                                'nw.active', 'nw.approved', 'nw.archive', 'nw.created', 'nw.modified'])
                    ->join('tb_user', 'nw.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

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
                } elseif ($column == 4) {
                    $query->order('nw.modified', $dir);
                }
            } else {
                $query->order('nw.id', 'desc');
            }

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $news = NewsModel::initialize($query);

            $count = NewsModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = [];
        if (null !== $news) {
            foreach ($news as $_news) {
                $label = '';
                if ($_news->active) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelRed'>Neaktivní</span>";
                }

                if ($_news->approved == NewsModel::STATE_APPROVED) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Schváleno</span>";
                } elseif ($_news->approved == NewsModel::STATE_REJECTED) {
                    $label .= "<span class='infoLabel infoLabelRed'>Zamítnuto</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelOrange'>Čeká na schválení</span>";
                }

                if ($this->getUser()->getId() == $_news->getUserId()) {
                    $label .= "<span class='infoLabel infoLabelGray'>Moje</span>";
                }

                if ($_news->archive) {
                    $label .= "<span class='infoLabel infoLabelGray'>Archivováno</span>";
                }

                $arr = [];
                $arr [] = '[ "' . $_news->getId() . '"';
                $arr [] = '"' . htmlentities($_news->getTitle()) . '"';
                $arr [] = '"' . $_news->getUserAlias() . '"';
                $arr [] = '"' . $_news->getCreated() . '"';
                $arr [] = '"' . $_news->getModified() . '"';
                $arr [] = '"' . $label . '"';

                $tempStr = '"';
                if ($this->isAdmin() || $_news->userId == $this->getUser()->getId()) {
                    $tempStr .= "<a href='/admin/news/edit/" . $_news->id . "' class='btn btn3 btn_pencil' title='Upravit'></a>";
                    $tempStr .= "<a href='/admin/news/delete/" . $_news->id . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }

                if ($this->isAdmin() && $_news->approved == 0) {
                    $tempStr .= "<a href='/admin/news/approvenews/" . $_news->id . "' class='btn btn3 btn_info ajaxReload' title='Schválit'></a>";
                    $tempStr .= "<a href='/admin/news/rejectnews/" . $_news->id . "' class='btn btn3 btn_stop ajaxReload' title='Zamítnout'></a>";
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
     * Show help for news section.
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
