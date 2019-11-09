<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use Admin\Model\PageContentHistoryModel;
use App\Model\PageContentModel;
use ReflectionException;
use THCFrame\Core\StringMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\Model\Exception\Validation;
use THCFrame\Request\RequestMethods;
use THCFrame\View\Exception\Data;

/**
 *
 */
class ContentController extends Controller
{

    /**
     * Get list of all content pages.
     *
     * @before _secured, _participant
     * @throws Data
     * @throws Connector
     * @throws Implementation
     */
    public function index(): void
    {
        $view = $this->getActionView();

        $content = PageContentModel::all();

        $view->set('content', $content);
    }

    /**
     * Create new page.
     *
     * @before _secured, _admin
     * @throws Data
     * @throws Validation
     * @throws Connector
     * @throws Implementation
     */
    public function add(): void
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
                'created' => date('Y-m-d H:i'),
                'modified' => date('Y-m-d H:i'),
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
     * @throws Data
     * @throws ReflectionException
     * @throws Validation
     * @throws Connector
     * @throws Implementation
     */
    public function edit($id): void
    {
        $view = $this->getActionView();

        $content = PageContentModel::first(['id = ?' => (int)$id]);

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
                PageContentHistoryModel::logChanges($originalContent, $content);
                $this->getCache()->clearCache();

                Event::fire('admin.log', ['success', 'Content id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Content id: ' . $id,
                    'Errors: ' . json_encode($errors + $content->getErrors()),
                ]);
                $view->set('errors', $content->getErrors())
                    ->set('content', $content);
            }
        }
    }

    /**
     * Return list of pages to insert page link to content.
     *
     * @before _secured, _participant
     * @throws Data
     * @throws Connector
     * @throws Implementation
     */
    public function insertToContent(): void
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;

        $contents = PageContentModel::all([], ['urlKey', 'title']);

        $view->set('contents', $contents);
    }

}
