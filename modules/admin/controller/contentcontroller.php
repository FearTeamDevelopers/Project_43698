<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class ContentController extends Controller
{

    /**
     * Check whether unique content identifier already exist or not
     * 
     * @param string $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = \App\Model\PageContentModel::first(array('urlKey = ?' => $key));

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Get list of all content pages
     * 
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();

        $content = \App\Model\PageContentModel::all();

        $view->set('content', $content);
    }

    /**
     * Create new page
     * 
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();

        $view->set('submstoken', $this->_mutliSubmissionProtectionToken())
                ->set('content', null);

        if (RequestMethods::post('submitAddContent')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/content/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('page'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Stránka s tímto názvem již existuje');
            }

            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));
            
            $content = new \App\Model\PageContentModel(array(
                'title' => RequestMethods::post('page'),
                'urlKey' => $urlKey,
                'body' => RequestMethods::post('text'),
                'bodyEn' => RequestMethods::post('texten'),
                'keywords' => $keywords,
                'metaTitle' => RequestMethods::post('metatitle'),
                'metaDescription' => RequestMethods::post('metadescription')
            ));

            if (empty($errors) && $content->validate()) {
                $id = $content->save();

                $this->getCache()->invalidate();
                Event::fire('admin.log', array('success', 'Content id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail', 'Errors: '.  json_encode($errors + $content->getErrors())));
                $view->set('errors', $errors + $content->getErrors())
                    ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                    ->set('content', $content);
            }
        }
    }
    
    /**
     * Edit existing page
     * 
     * @before _secured, _admin
     * @param int   $id     page id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $content = \App\Model\PageContentModel::first(array('id = ?' => (int) $id));

        if (NULL === $content) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/content/');
        }

        $view->set('content', $content);

        if (RequestMethods::post('submitEditContent')) {
            if($this->_checkCSRFToken() !== true){
                self::redirect('/admin/content/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('page'));

            if ($content->getUrlKey() !== $urlKey && !$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Stránka s tímto názvem již existuje');
            }

            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));
            
            $content->title = RequestMethods::post('page');
            $content->urlKey = $urlKey;
            $content->body = RequestMethods::post('text');
            $content->bodyEn = RequestMethods::post('texten');
            $content->keywords = $keywords;
            $content->metaTitle = RequestMethods::post('metatitle');
            $content->metaDescription = RequestMethods::post('metadescription');
            $content->active = RequestMethods::post('active');

            if (empty($errors) && $content->validate()) {
                $content->save();
                
                $this->getCache()->invalidate();
                Event::fire('admin.log', array('success', 'Content id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail', 'Content id: ' . $id,
                    'Errors: '.  json_encode($errors + $content->getErrors())));
                $view->set('errors', $content->getErrors())
                    ->set('content', $content);
            }
        }
    }
    
    /**
     * Return list of pages to insert page link to content
     * 
     * @before _secured, _participant
     */
    public function insertToContent()
    {
        $view = $this->getActionView();
        $this->willRenderLayoutView = false;
        
        $content = \App\Model\PageContentModel::all(array(), array('urlKey', 'title'));
        
        $view->set('contents', $content);
    }
}
