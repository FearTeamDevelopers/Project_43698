<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Core\StringMethods;

/**
 * 
 */
class Admin_Controller_Content extends Controller
{

    /**
     * Check whether unique content identifier already exist or not
     * 
     * @param string $key
     * @return boolean
     */
    private function _checkUrlKey($key)
    {
        $status = App_Model_PageContent::first(array('urlKey = ?' => $key));

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

        $content = App_Model_PageContent::all();

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

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddContent')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/content/');
            }
            
            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('page'));

            if (!$this->_checkUrlKey($urlKey)) {
                $errors['title'] = array('Stránka s tímto názvem již existuje');
            }

            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));
            
            $content = new App_Model_PageContent(array(
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
                $view->successMessage('Obsah'.self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/content/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $content->getErrors())
                    ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
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

        $content = App_Model_PageContent::first(array('id = ?' => (int) $id));

        if (NULL === $content) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/content/');
        }

        $view->set('content', $content);

        if (RequestMethods::post('submitEditContent')) {
            if($this->checkCSRFToken() !== true){
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
                Event::fire('admin.log', array('fail', 'Content id: ' . $id));
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
        
        $content = App_Model_PageContent::all(array(), array('urlKey', 'title'));
        
        $view->set('contents', $content);
    }
}
