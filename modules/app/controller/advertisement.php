<?php

use App\Etc\Controller;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class App_Controller_Advertisement extends Controller
{

    /**
     * 
     * @param type $str
     * @return boolean
     */
    private function _checkAdKey($str)
    {
        $ad = App_Model_Advertisement::first(array('uniqueKey = ?' => $str));
        
        if($ad === null){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 
     */
    public function index()
    {
        $view = $this->getActionView();
        $latestFiveAds = App_Model_Advertisement::fetchLatestFive();
        $sections = App_Model_AdSection::fetchAllActive();

        $view->set('adsections', $sections)
                ->set('latestfiveads', $latestFiveAds);
    }

    /**
     * 
     * @param type $page
     */
    public function listByTypeUrlkey($type, $urlkey, $page = 1)
    {
        $view = $this->getActionView();
        
        if($type == 'nabidka'){
            $ads = App_Model_Advertisement::fetchActiveByTypeSection('tender', $urlkey, $page);
        }elseif($type == 'poptavka'){
            $ads = App_Model_Advertisement::fetchActiveByTypeSection('demand', $urlkey, $page);
        }else{
            self::redirect('/nenalezeno');
        }
        
        $view->set('ads', $ads);
    }

    /**
     * 
     * @param type $page
     */
    public function listByType($type, $page = 1)
    {
        $view = $this->getActionView();
        
        if($type == 'nabidka'){
            $ads = App_Model_Advertisement::fetchActiveByType('tender', $page);
        }elseif($type == 'poptavka'){
            $ads = App_Model_Advertisement::fetchActiveByType('demand', $page);
        }else{
            self::redirect('/nenalezeno');
        }
        
        $view->set('ads', $ads);
    }
    
    /**
     * 
     */
    public function listByUser()
    {
        $view = $this->getActionView();
        
        $ads = App_Model_Advertisement::fetchActiveByUser($this->getUser()->getId());
        
        $view->set('ads', $ads);
    }

    /**
     * 
     * @param type $urlkey
     */
    public function detail($urlkey)
    {
        $view = $this->getActionView();
        $ad = App_Model_Advertisement::fetchActiveByKey($urlkey);
        
        if($ad === null){
            self::redirect('/nenalezeno');
        }
        
        $view->set('ad', $ad);
    }

    /**
     * 
     */
    public function search()
    {
        $searchString = StringMethods::sanitize(RequestMethods::get('najit'), "()[],.<>*$@/\\\'\";:");
        
        //$query = str_replace(array('.', ',', '_', '(', ')', '[', ']', '|'), '', $query);
        //$query = str_replace(array('?', '!', '@', '&', '*', ':', '+', '=', '~', '°', '´', '`', '%', "'", '"'), '', $query);
    }
    
    /**
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();
        
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());
        
        if (RequestMethods::post('submitAddAdvertisement')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/bazar');
            }

            $errors = array();
            $uniqueKey = sha1(RequestMethods::post('title').RequestMethods::post('content').$this->getUser()->getId());

            if (!$this->_checkAdKey($uniqueKey)) {
                $errors['title'] = array('Nepodařilo se vytvořit identifikátor inzerátu');
            }
            
            $ad = new App_Model_Advertisement(array(
                'title' => RequestMethods::post('title'),
                'userId' => $this->getUser()->getId(),
                'sectionId' => RequestMethods::post('section'),
                'uniqueKey' => $uniqueKey,
                'adtype' => RequestMethods::post('type'),
                'userAlias' => $this->getUser()->getWholeName(),
                'content' => RequestMethods::post('content'),
                'expirationDate' => RequestMethods::post('expiration'),
                'keywords' => RequestMethods::post('keywords')
            ));

            if (empty($errors) && $ad->validate()) {
                $id = $ad->save();

                $view->successMessage('Gallery' . self::SUCCESS_MESSAGE_1);
                self::redirect('/bazar/'.$ad->getUniqueKey());
            } else {
                $view->set('ad', $ad)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $ad->getErrors());
            }
        }
    }
    
    /**
     * @before _secured, _member
     * @param type $uniqueKey
     */
    public function edit($uniqueKey)
    {
        $view = $this->getActionView();

        $ad = App_Model_Advertisement::first(array('uniqueKey = ?' => $uniqueKey));

        if (NULL === $ad) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/bazar');
        }
       
        $view->set('ad', $ad);

        if (RequestMethods::post('submitEditAdvertisement')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/bazar');
            }
            
            $errors = array();
            $uniqueKey = sha1(RequestMethods::post('title').RequestMethods::post('content').$this->getUser()->getId());

            if ($ad->getUniqueKey() !== $uniqueKey && !$this->_checkAdKey($uniqueKey)) {
                $errors['title'] = array('Nepodařilo se vytvořit identifikátor inzerátu');
            }

            $ad->title = RequestMethods::post('title');
            $ad->uniqueKey = $uniqueKey;
            $ad->adtype = RequestMethods::post('type');
            $ad->sectionId = RequestMethods::post('section');
            $ad->content = RequestMethods::post('content');
            $ad->expirationDate = RequestMethods::post('expiration');
            $ad->keywords = RequestMethods::post('keywords');

            if (empty($errors) && $ad->validate()) {
                $ad->save();

                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/bazar/'.$ad->getUniqueKey());
            } else {
                $view->set('errors', $ad->getErrors());
            }
        }
    }

    /**
     * @before _secured, _member
     * @param type $uniqueKey
     */
    public function delete($uniqueKey)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $ad = App_Model_Advertisement::first(array('uniqueKey = ?' => $uniqueKey));

        if (NULL === $ad) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($ad->delete()) {
                echo 'success';
            } else {
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

}
