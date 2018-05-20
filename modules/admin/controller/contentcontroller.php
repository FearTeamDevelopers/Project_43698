<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;
use App\Model\PageContentModel;

/**
 *
 */
class ContentController extends Controller
{

    /**
     * Get list of all content pages.
     *
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();

        $content = PageContentModel::all();

        $view->set('content', $content);
    }

    /**
     * Create new page.
     *
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('content', null);

        if (RequestMethods::post('submitAddContent')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/content/');
            }

            $errors = [];
            $urlKey = StringMethods::createUrlKey(RequestMethods::post('page'));

            if (!PageContentModel::checkUrlKey($urlKey)) {
                $errors['title'] = [$this->lang('ARTICLE_TITLE_IS_USED')];
            }

            $metaDesc = substr(strip_tags(RequestMethods::post('text')), 0, 600);
            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

            $content = new PageContentModel([
                'title' => RequestMethods::post('page'),
                'urlKey' => $urlKey,
                'body' => RequestMethods::post('text'),
                'bodyEn' => RequestMethods::post('texten'),
                'keywords' => $keywords,
                'metaTitle' => RequestMethods::post('metatitle'),
                'metaDescription' => RequestMethods::post('metadescription', $metaDesc),
            ]);

            if (empty($errors) && $content->validate()) {
                $id = $content->save();
                $this->getCache()->clearCache();

                Event::fire('admin.log', ['success', 'Content id: ' . $id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($errors + $content->getErrors())]);
                $view->set('errors', $errors + $content->getErrors())
                        ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                        ->set('content', $content);
            }
        }
    }

    /**
     * Edit existing page.
     *
     * @before _secured, _admin
     *
     * @param int $id page id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $content = PageContentModel::first(['id = ?' => (int) $id]);

        if (null === $content) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/content/');
        }

        $view->set('content', $content);

        if (RequestMethods::post('submitEditContent')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/content/');
            }

            $errors = [];
            $originalContent = clone $content;
            $urlKey = StringMethods::createUrlKey(RequestMethods::post('page'));

            if ($content->getUrlKey() !== $urlKey && !PageContentModel::checkUrlKey($urlKey)) {
                $errors['title'] = [$this->lang('ARTICLE_TITLE_IS_USED')];
            }

            $metaDesc = substr(strip_tags(RequestMethods::post('text')), 0, 600);
            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

            $content->title = RequestMethods::post('page');
            $content->urlKey = $urlKey;
            $content->body = RequestMethods::post('text');
            $content->bodyEn = RequestMethods::post('texten');
            $content->keywords = $keywords;
            $content->metaTitle = RequestMethods::post('metatitle');
            $content->metaDescription = RequestMethods::post('metadescription', $metaDesc);
            $content->active = RequestMethods::post('active');

            if (empty($errors) && $content->validate()) {
                $content->save();
                \Admin\Model\PageContentHistoryModel::logChanges($originalContent, $content);
                $this->getCache()->clearCache();

                Event::fire('admin.log', ['success', 'Content id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', ['fail', 'Content id: ' . $id,
                    'Errors: ' . json_encode($errors + $content->getErrors()),]);
                $view->set('errors', $content->getErrors())
                        ->set('content', $content);
            }
        }
    }

    /**
     * Return list of pages to insert page link to content.
     *
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $contents = PageContentModel::all([], ['urlKey', 'title']);

        $view->set('contents', $contents);
    }

}
