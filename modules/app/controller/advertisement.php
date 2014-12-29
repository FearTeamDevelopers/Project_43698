<?php

use App\Etc\Controller;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class App_Controller_Advertisement extends Controller
{

    /**
     * 
     * @param type $str
     * @param type $stopWordsCs
     * @param type $stopWordsEn
     * @return type
     */
    private function _cleanString($str)
    {
        $cleanStr = StringMethods::removeDiacriticalMarks($str);
        $cleanStr = strtolower(trim($cleanStr));
        $cleanStr = preg_replace('/[^a-z0-9\s]+/', ' ', $cleanStr);
        $cleanStr2 = preg_replace('/\s+/', ' ', $cleanStr);

        unset($cleanStr);
        return $cleanStr2;
    }
    
    /**
     * 
     * @param App_Model_Advertisement $ad
     * @param App_Model_AdMessage $message
     * @return string
     */
    private function _getEmailBody(App_Model_Advertisement $ad, App_Model_AdMessage $message)
    {
        $body = '<div>'
                . '<strong>Dotaz k inzerátu<strong><br/>'
                . 'Byl odeslán následující dotaz k inzerátu na serveru <a href="http://'.$this->getServerHost().'">Hastrman</a>:'
                . '<br/><br/>'
                . 'Inzerát: <a href="http://'.$this->getServerHost().'/bazar/r/'.$ad->getUniqueKey().'">'.$ad->getTitle().'</a><br/>'
                . 'Jméno: '.$message->getMsAuthor().'<br/>'
                . 'Email: '.$message->getMsEmail().'<br/>'
                . 'Text: <br/>'.$message->getMessage().'<br/>';
        
        return $body;
    }

    /**
     * 
     * @param type $str
     * @return boolean
     */
    private function _checkAdKey($str)
    {
        $ad = App_Model_Advertisement::first(array('uniqueKey = ?' => $str));

        if ($ad === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $adsPerPage = 10;
        
        $adSections = App_Model_AdSection::fetchAllActive();

        $view->set('adsections', $adSections);

        if($page <= 0){
            $page = 1;
        }
        
        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/bazar';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/p/' . $page;
        }
        
        $content = $this->getCache()->get('bazar-' . $page);
        
        if ($content !== null) {
            $ads = $content;
        } else {
            $ads = App_Model_Advertisement::fetchAdsActive($adsPerPage, $page);

            $this->getCache()->set('bazar-' . $page, $ads);
        }

        $adsCount = App_Model_Advertisement::count(
                        array('active = ?' => true,
                            'expirationDate >= ?' => date('Y-m-d H:i:s'))
        );
        
        $adsPageCount = ceil($adsCount / $adsPerPage);

        if ($adsPageCount > 1) {
            $prevPage = $page - 1;
            $nextPage = $page + 1;

            if ($nextPage > $adsPageCount) {
                $nextPage = 0;
            }

            $layoutView
                    ->set('pagedprev', $prevPage)
                    ->set('pagedprevlink', '/bazar/p/' . $prevPage)
                    ->set('pagednext', $nextPage)
                    ->set('pagednextlink', '/bazar/p/' . $nextPage);
        }

        $view->set('news', $ads)
                ->set('currentpage', $page)
                ->set('pagecount', $adsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar');
        
    }

    /**
     * 
     * @param type $page
     */
    public function filter($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $adsPerPage = 10;
        
        $adSections = App_Model_AdSection::fetchAllActive();
        $view->set('adsections', $adSections);

        $type = RequestMethods::post('bftype');
        $section = RequestMethods::post('bfsection');
        
        if($page <= 0){
            $page = 1;
        }
        
        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/filtr';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/filtr/' . $page;
        }

        if ($section == '0') {
            if ($type == 'nabidka') {
                $ads = App_Model_Advertisement::fetchActiveByType('tender', $adsPerPage, $page);
                $adsCount = App_Model_Advertisement::countActiveByType('tender');
            } elseif ($type == 'poptavka') {
                $ads = App_Model_Advertisement::fetchActiveByType('demand', $adsPerPage, $page);
                $adsCount = App_Model_Advertisement::countActiveByType('demand');
            } else {
                $this->_willRenderActionView = false;
                self::redirect('/bazar/nenalezeno');
            }
        } else {
            if ($type == 'nabidka') {
                $ads = App_Model_Advertisement::fetchActiveByTypeSection('tender', $section, $adsPerPage, $page);
                $adsCount = App_Model_Advertisement::countActiveByTypeSection('tender', $section);
            } elseif ($type == 'poptavka') {
                $ads = App_Model_Advertisement::fetchActiveByTypeSection('demand', $section, $adsPerPage, $page);
                $adsCount = App_Model_Advertisement::countActiveByTypeSection('demand', $section);
            } else {
                $this->_willRenderActionView = false;
                self::redirect('/bazar/nenalezeno');
            }
        }

        $adsPageCount = ceil($adsCount / $adsPerPage);

        if ($adsPageCount > 1) {
            $prevPage = $page - 1;
            $nextPage = $page + 1;

            if ($nextPage > $adsPageCount) {
                $nextPage = 0;
            }

            $layoutView
                    ->set('pagedprev', $prevPage)
                    ->set('pagedprevlink', '/bazar/filtr/' . $prevPage)
                    ->set('pagednext', $nextPage)
                    ->set('pagednextlink', '/bazar/filtr/' . $nextPage);
        }

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagecount', $adsPageCount)
                ->set('bftype', $type)
                ->set('bfsection', $section);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar');
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
     * @param type $uniquekey
     */
    public function detail($uniquekey)
    {
        $view = $this->getActionView();
        $ad = App_Model_Advertisement::fetchActiveByKey($uniquekey);

        if ($ad === null) {
            $this->_willRenderActionView = false;
            self::redirect('/nenalezeno');
        }

        $view->set('ad', $ad)
                ->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAdReply')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/bazar/r/' . RequestMethods::post('aduniquekey'));
            }

            $message = new App_Model_AdMessage(array(
                'adId' => $ad->getId(),
                'msAuthor' => RequestMethods::post('name'),
                'msEmail' => RequestMethods::post('email'),
                'message' => RequestMethods::post('message'),
                'sendEmailCopy' => RequestMethods::post('getemailcopy', 0),
                'messageSent' => ''
            ));

            if ($message->validate()) {
                require_once APP_PATH . '/vendors/swiftmailer/swift_required.php';
                $transport = Swift_MailTransport::newInstance();
                $mailer = Swift_Mailer::newInstance($transport);

                $email = Swift_Message::newInstance()
                        ->setSubject('Hastrman - Bazar - Dotaz k inzerátu')
                        ->setFrom('bazar@hastrman.cz')
                        ->setBody($this->_getEmailBody($ad, $message));
                
                if ($message->getSendEmailCopy() == 1) {
                    $email->setTo($message->getMsEmail(), $ad->getEmail());
                } else {
                    $email->setTo($ad->getEmail());
                }
                
                $mailer->send($email);
                
                $message->messageSent = 1;
                $message->save();
                
                $view->successMessage('Dotaz byl úspěšně odeslán');
            }else{
                $view->set('errors', $message->getErrors())
                    ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                    ->set('admessage', $message);
            }
        }
    }

    /**
     * 
     */
    public function search()
    {
        $db = Registry::get('database');
        $ssql = "SELECT *, SUM(MATCH(title, content, keywords) AGAINST(? IN BOOLEAN MODE)) as score "
                . "FROM tb_advertisement "
                . "WHERE active=1 AND expirationDate >= " . date('Y-m-d H:i:s') . " MATCH(title, content, keywords) AGAINST(? IN BOOLEAN MODE) "
                . "ORDER BY score DESC, created DESC";

        $query = $this->_cleanString(RequestMethods::get('adstr'));
        $words = explode(' ', $query);

        foreach ($words as &$word) {
            $word = '+' . $word;
        }

        $searchCond = '(' . implode(' ', $words) . ') ("' . $query . '")';
        $result = $db->execute($ssql, $searchCond, $searchCond);
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
            $uniqueKey = sha1(RequestMethods::post('title') . RequestMethods::post('content') . $this->getUser()->getId());

            if (!$this->_checkAdKey($uniqueKey)) {
                $errors['title'] = array('Takovýto inzerát už nejspíše existuje');
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

                Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                $view->successMessage('Inzerát' . self::SUCCESS_MESSAGE_1);
                self::redirect('/bazar/' . $ad->getUniqueKey());
            } else {
                Event::fire('admin.log', array('fail'));
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

        $ad = App_Model_Advertisement::first(array('uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()));

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
            $uniqueKey = sha1(RequestMethods::post('title') . RequestMethods::post('content') . $this->getUser()->getId());

            if ($ad->getUniqueKey() !== $uniqueKey && !$this->_checkAdKey($uniqueKey)) {
                $errors['title'] = array('Takovýto inzerát už nejspíše existuje');
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

                Event::fire('admin.log', array('success', 'Ad id: ' . $ad->getId()));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/bazar/' . $ad->getUniqueKey());
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $ad->getId()));
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

        $ad = App_Model_Advertisement::first(array('uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()));

        if (NULL === $ad) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($ad->delete()) {
                Event::fire('admin.log', array('success', 'Ad id: ' . $ad->getId()));
                echo 'success';
            } else {
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

}
