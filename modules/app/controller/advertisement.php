<?php

use App\Etc\Controller;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;

/**
 * 
 */
class App_Controller_Advertisement extends Controller
{

    /**
     * Clean string. Cleaned string contains only [a-z0-9\s]
     * 
     * @param string $str
     * @return string
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
     * Prepare email body
     * 
     * @param App_Model_Advertisement $ad
     * @param App_Model_AdMessage $message
     * @return string
     */
    private function _getEmailBody(App_Model_Advertisement $ad, App_Model_AdMessage $message)
    {
        $body = '<div>'
                . '<strong>Dotaz k inzerátu<strong><br/>'
                . 'Byl odeslán následující dotaz k inzerátu na serveru <a href="http://' . $this->getServerHost() . '">Hastrman</a>:'
                . '<br/><br/>'
                . 'Inzerát: <a href="http://' . $this->getServerHost() . '/bazar/r/' . $ad->getUniqueKey() . '">' . $ad->getTitle() . '</a><br/>'
                . 'Jméno: ' . $message->getMsAuthor() . '<br/>'
                . 'Email: ' . $message->getMsEmail() . '<br/>'
                . 'Text: <br/>' . $message->getMessage() . '<br/>';

        return $body;
    }

    /**
     * Check whether ad unique identifier already exist or not
     * 
     * @param string $str
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
     * Get list of ads
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $adsPerPage = 10;

        $adSections = App_Model_AdSection::all(array('active = ?' => true));

        $view->set('adsections', $adSections);

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/bazar';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/p/' . $page;
        }

        $content = $this->getCache()->get('bazar-' . $page);

        if (null !== $content) {
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

        $this->_pagerMetaLinks($adsPageCount, $page, '/bazar/p/');

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/bazar')
                ->set('pagecount', $adsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar');
    }

    /**
     * Ads filter
     * 
     * @param int $page
     */
    public function filter($page = 1)
    {
        if ($this->checkCSRFToken() !== true) {
            self::redirect('/bazar');
        }

        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $adsPerPage = 10;

        $adSections = App_Model_AdSection::all(array('active = ?' => true));
        $view->set('adsections', $adSections);

        $type = RequestMethods::post('bftype');
        $section = RequestMethods::post('bfsection');

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/filtr';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/filtr/p/' . $page;
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

        $this->_pagerMetaLinks($adsPageCount, $page, '/bazar/filtr/p/');

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/bazar/filtr')
                ->set('pagecount', $adsPageCount)
                ->set('bftype', $type)
                ->set('bfsection', $section);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar');
    }

    /**
     * Get list of ads created by user currently logged id
     */
    public function listByUser($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $userId = $this->getUser()->getId();
        $adsPerPage = 10;

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/moje-inzeray';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/moje-inzeray/p/' . $page;
        }

        $ads = App_Model_Advertisement::fetchActiveByUser($userId, $adsPerPage, $page);
        $adsCount = App_Model_Advertisement::countActiveByUser($userId);

        $adsPageCount = ceil($adsCount / $adsPerPage);

        $this->_pagerMetaLinks($adsPageCount, $page, '/bazar/moje-inzeraty/p/');

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/bazar/moje-inzeraty')
                ->set('pagecount', $adsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar - Moje inzeráty');
    }

    /**
     * Show ad detail
     * 
     * @param string    $uniquekey      ad key
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
                $transport = Swift_SmtpTransport::newInstance('smtp.ebola.cz', 465, 'ssl')
                                ->setUsername('info@fear-team.cz')
                                ->setPassword('ThcMInfo-2014*');
                $mailer = Swift_Mailer::newInstance($transport);

                $email = Swift_Message::newInstance()
                        ->setSubject('Hastrman - Bazar - Dotaz k inzerátu')
                        ->setFrom('bazar@hastrman.cz')
                        ->setBody($this->_getEmailBody($ad, $message));

                if ($message->getSendEmailCopy() == 1) {
                    $email->setTo(array($message->getMsEmail(), $ad->getEmail()));
                } else {
                    $email->setTo($ad->getEmail());
                }

                //$mailer->send($email);

                $message->messageSent = 1;
                $message->save();

                $view->successMessage('Dotaz byl úspěšně odeslán');
            } else {
                $view->set('errors', $message->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('admessage', $message);
            }
        }
    }
    
    /**
     * Search in ads
     * 
     * @param int $page
     */
    public function search($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $query = $this->_cleanString(RequestMethods::get('adstr'));
        $articlesPerPage = $this->getConfig()->bazaar_search_results_per_page;

        $db = Registry::get('database');
        $sqlTemplate = "SELECT uniqueKey, adtype, userAlias, title, price, created, MATCH(title, content, keywords) AGAINST( %s IN BOOLEAN MODE) as score "
                . "FROM tb_advertisement "
                . "WHERE active=1 AND expirationDate >= '%s' AND MATCH(title, content, keywords) AGAINST( %s IN BOOLEAN MODE) "
                . "ORDER BY score DESC, created DESC "
                . "LIMIT %s, %s";

        $words = explode(' ', $query);

        foreach ($words as &$word) {
            $word = '+' . $word;
        }

        $searchCond = "'".implode(' ', $words) . ' "' . $query . '"\'';
        $sql = sprintf($sqlTemplate, $searchCond, date('Y-m-d'), $searchCond, $page-1, $articlesPerPage);
        $searchResult = $db->execute($sql);
        
        $rows = array();

        for ($i = 0; $i < $searchResult->num_rows; $i++) {
            $rows[] = $searchResult->fetch_array(MYSQLI_ASSOC);
        }

        print('<pre>'.print_r($rows, true).'</pre>');
        //var_dump($searchResult);
        die;

        $view->set('result', $searchResult)
                ->set('currentpage', $page);
        $layoutView->set('metatitle', 'Hastrman - Bazar - Hledat')
                ->set('pagerpathprefix', '/prohledatbazar')
                ->set('pagerpathpostfix', '?' . http_build_query($query));
    }

    /**
     * Create new ad
     * 
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

            $errors = $uploadErrors = array();
            $uniqueKey = sha1(RequestMethods::post('title') . RequestMethods::post('content') . $this->getUser()->getId());

            if (!$this->_checkAdKey($uniqueKey)) {
                $errors['title'] = array('Takovýto inzerát už nejspíše existuje');
            }

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->getConfig()->thumb_width,
                'thumbHeight' => $this->getConfig()->thumb_height,
                'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                'maxImageHeight' => $this->getConfig()->photo_maxheight
            ));

            $fileErrors = $fileManager->uploadImage('uploadfile', 'ads', time() . '_', true)->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                $uploadErrors += $fileErrors;
            }

            $adTtl = $this->getConfig()->bazar_ad_ttl;
            $date = new DateTime();
            $date->add(new DateInterval('P' . (int) $adTtl . 'D'));
            $expirationDate = $date->format('Y-m-d');

            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

            $ad = new App_Model_Advertisement(array(
                'title' => RequestMethods::post('title'),
                'userId' => $this->getUser()->getId(),
                'sectionId' => RequestMethods::post('section'),
                'uniqueKey' => $uniqueKey,
                'adtype' => RequestMethods::post('type'),
                'userAlias' => $this->getUser()->getWholeName(),
                'content' => RequestMethods::post('content'),
                'price' => RequestMethods::post('price', 0),
                'expirationDate' => $expirationDate,
                'keywords' => $keywords
            ));

            if (empty($errors) && $ad->validate()) {
                $id = $ad->save();

                if (!empty($files)) {
                    $files = array_slice($files, 0, 3);
                    
                    foreach ($files as $i => $file) {
                        if ($file instanceof \THCFrame\Filesystem\Image) {
                            $adImage = new App_Model_AdImage(array(
                                'adId' => $id,
                                'userId' => $this->getUser()->getId(),
                                'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                                'imgMain' => trim($file->getFilename(), '.'),
                                'imgThumb' => trim($file->getThumbname(), '.')
                            ));

                            if ($adImage->validate()) {
                                $adImageId = $adImage->save();

                                Event::fire('app.log', array('success', 'Photo id: ' . $adImageId . ' in ad ' . $id));
                            } else {
                                Event::fire('app.log', array('fail', 'Upload photo for ad ' . $id));
                                $uploadErrors += $adImage->getErrors();
                            }
                        }
                    }

                    $errors['uploadfile'] = $uploadErrors;

                    if (empty($errors['uploadfile'])) {
                        Event::fire('app.log', array('success', 'Ad id: ' . $id));
                        $view->successMessage('Inzerát' . self::SUCCESS_MESSAGE_1);
                        self::redirect('/bazar/r/' . $ad->getUniqueKey());
                    } else {
                        Event::fire('app.log', array('fail'));
                        $view->set('ad', $ad)
                                ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                                ->set('errors', $errors + $ad->getErrors());
                    }
                } else {
                    Event::fire('app.log', array('success', 'Ad id: ' . $id));
                    $view->successMessage('Inzerát' . self::SUCCESS_MESSAGE_1);
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                }
            } else {
                Event::fire('app.log', array('fail'));
                $view->set('ad', $ad)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $errors + $ad->getErrors());
            }
        }
    }

    /**
     * Edit existing ad
     * 
     * @before _secured, _member
     * @param string    $uniqueKey      ad key
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

            $errors = $uploadErrors = array();
            $uniqueKey = sha1(RequestMethods::post('title') . RequestMethods::post('content') . $this->getUser()->getId());

            if ($ad->getUniqueKey() !== $uniqueKey && !$this->_checkAdKey($uniqueKey)) {
                $errors['title'] = array('Takovýto inzerát už nejspíše existuje');
            }

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->getConfig()->thumb_width,
                'thumbHeight' => $this->getConfig()->thumb_height,
                'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                'maxImageHeight' => $this->getConfig()->photo_maxheight
            ));

            $fileErrors = $fileManager->uploadImage('uploadfile', 'ads', time() . '_', true)->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($fileErrors)) {
                $uploadErrors += $fileErrors;
            }

            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

            $ad->title = RequestMethods::post('title');
            $ad->uniqueKey = $uniqueKey;
            $ad->adtype = RequestMethods::post('type');
            $ad->sectionId = RequestMethods::post('section');
            $ad->content = RequestMethods::post('content');
            $ad->price = RequestMethods::post('price', 0);
            $ad->keywords = $keywords;

            if (empty($errors) && $ad->validate()) {
                $ad->save();

                if (!empty($files)) {
                    $currentPhotoCount = App_Model_AdImage::count(array('adId = ?' => $ad->getId()), array('id'));
                    $files = array_slice($files, 0, 3 - $currentPhotoCount);

                    if (!empty($files)) {
                        foreach ($files as $i => $file) {
                            if ($file instanceof \THCFrame\Filesystem\Image) {
                                $adImage = new App_Model_AdImage(array(
                                    'adId' => $ad->getId(),
                                    'userId' => $this->getUser()->getId(),
                                    'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                                    'imgMain' => trim($file->getFilename(), '.'),
                                    'imgThumb' => trim($file->getThumbname(), '.')
                                ));

                                if ($adImage->validate()) {
                                    $adImageId = $adImage->save();

                                    Event::fire('app.log', array('success', 'Photo id: ' . $adImageId . ' in ad ' . $ad->getId()));
                                } else {
                                    Event::fire('app.log', array('fail', 'Upload photo for ad ' . $ad->getId()));
                                    $uploadErrors += $adImage->getErrors();
                                }
                            }
                        }

                        $errors['uploadfile'] = $uploadErrors;

                        if (empty($errors['uploadfile'])) {
                            Event::fire('app.log', array('success', 'Ad id: ' . $ad->getId()));
                            $view->successMessage(self::SUCCESS_MESSAGE_2);
                            self::redirect('/bazar/r/' . $ad->getUniqueKey());
                        } else {
                            Event::fire('app.log', array('fail'));
                            $view->set('errors', $errors + $ad->getErrors());
                        }
                    } else {
                        Event::fire('app.log', array('success', 'Ad id: ' . $ad->getId()));
                        $view->successMessage(self::SUCCESS_MESSAGE_2 . ', ale více fotek už není možné nahrát');
                        self::redirect('/bazar/r/' . $ad->getUniqueKey());
                    }
                } else {
                    Event::fire('admin.log', array('success', 'Ad id: ' . $ad->getId()));
                    $view->successMessage(self::SUCCESS_MESSAGE_2);
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                }
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $ad->getId()));
                $view->set('errors', $errors + $ad->getErrors());
            }
        }
    }

    /**
     * Delete existing ad
     * 
     * @before _secured, _member
     * @param string    $uniqueKey      ad key
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
                Event::fire('admin.log', array('fail', 'Ad id: ' . $ad->getId()));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * Delete ad image
     * 
     * @before _secured, _member
     * @param int   $id     image id
     */
    public function deleteAdImage($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $adImage = App_Model_AdImage::first(array('id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()));

        if (NULL === $adImage) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $imgMain = $adImage->getUnlinkPath();
            $imgThumb = $adImage->getUnlinkThumbPath();

            if ($adImage->delete()) {
                @unlink($imgMain);
                @unlink($imgThumb);

                Event::fire('admin.log', array('success', 'AdImage id: ' . $adImage->getId()));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'AdImage id: ' . $adImage->getId()));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * Create request for availability extend
     * 
     * @before _secured, _member
     * @param string    $uniqueKey      ad key
     */
    public function sendAvailabilityExtendRequest($uniqueKey)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $ad = App_Model_Advertisement::first(array('uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()));

        if (NULL === $ad) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $ad->hasAvailabilityRequest = true;

            if ($ad->validate()) {
                $ad->save();
                Event::fire('admin.log', array('success', 'Ad id: ' . $ad->getId()));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $ad->getId()));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

}
