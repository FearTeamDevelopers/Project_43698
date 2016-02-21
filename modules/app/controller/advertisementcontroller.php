<?php

namespace App\Controller;

use App\Etc\Controller;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Request\Request;

/**
 *
 */
class AdvertisementController extends Controller
{

    /**
     * Clean string. Cleaned string contains only [a-z0-9\s].
     *
     * @param string $str
     *
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
     * Check whether ad unique identifier already exist or not.
     *
     * @param string $str
     *
     * @return bool
     */
    private function _checkAdKey($str)
    {
        $ad = \App\Model\AdvertisementModel::first(array('uniqueKey = ?' => $str));

        if ($ad === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if are set specific metadata or leave their default values.
     */
    private function _checkMetaData($layoutView,
            \App\Model\AdvertisementModel $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaTitle() == '') {
            $layoutView->set('metatitle', 'Hastrman - Bazar - ' . $object->getTitle());
        } else {
            $layoutView->set('metatitle', 'Hastrman - Bazar - ' . $object->getMetaTitle());
        }

        $canonical = 'http://' . $this->getServerHost() . '/bazar/r/' . $object->getUniqueKey();

        $layoutView->set('canonical', $canonical)
                ->set('article', 1)
                ->set('articlecreated', $object->getCreated())
                ->set('articlemodified', $object->getModified())
                ->set('metaogurl', "http://{$this->getServerHost()}{$uri}")
                ->set('metaogtype', 'article');
    }

    /**
     * Get list of ads.
     */
    public function index($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $adsPerPage = 10;

        $adSections = \App\Model\AdSectionModel::all(array('active = ?' => true));

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
            $ads = \App\Model\AdvertisementModel::fetchAdsActive($adsPerPage, $page);

            $this->getCache()->set('bazar-' . $page, $ads);
        }

        $adsCount = \App\Model\AdvertisementModel::count(
                        array('active = ?' => true,
                            'expirationDate >= ?' => date('Y-m-d H:i:s'),)
        );

        $adsPageCount = ceil($adsCount / $adsPerPage);

        $this->pagerMetaLinks($adsPageCount, $page, '/bazar/p/');

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/bazar')
                ->set('pagecount', $adsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar');
    }

    /**
     * Ads filter.
     *
     * @param int $page
     */
    public function filter($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $adsPerPage = 10;

        $adSections = \App\Model\AdSectionModel::all(array('active = ?' => true));
        $view->set('adsections', $adSections);

        $type = RequestMethods::get('bftype', '0');
        $section = RequestMethods::get('bfsection', '0');

        $httpQuery = '?' . http_build_query(array('bftype' => $type, 'bfsection' => $section));

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/filtr';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/filtr/p/' . $page;
        }

        if ($type == '0' && $section == '0') {
            $this->_willRenderActionView = false;
            self::redirect('/bazar');
        }

        if ($section == '0') {
            if ($type == 'nabidka') {
                $ads = \App\Model\AdvertisementModel::fetchActiveByType('tender', $adsPerPage, $page);
                $adsCount = \App\Model\AdvertisementModel::countActiveByType('tender');
            } elseif ($type == 'poptavka') {
                $ads = \App\Model\AdvertisementModel::fetchActiveByType('demand', $adsPerPage, $page);
                $adsCount = \App\Model\AdvertisementModel::countActiveByType('demand');
            } else {
                $this->_willRenderActionView = false;
                self::redirect('/bazar/nenalezeno');
            }
        } else {
            if ($type == 'nabidka') {
                $ads = \App\Model\AdvertisementModel::fetchActiveByTypeSection('tender', $section, $adsPerPage, $page);
                $adsCount = \App\Model\AdvertisementModel::countActiveByTypeSection('tender', $section);
            } elseif ($type == 'poptavka') {
                $ads = \App\Model\AdvertisementModel::fetchActiveByTypeSection('demand', $section, $adsPerPage, $page);
                $adsCount = \App\Model\AdvertisementModel::countActiveByTypeSection('demand', $section);
            } else {
                $ads = \App\Model\AdvertisementModel::fetchActiveBySection($section, $adsPerPage, $page);
                $adsCount = \App\Model\AdvertisementModel::countActiveBySection($section);
            }
        }

        $adsPageCount = ceil($adsCount / $adsPerPage);

        $this->pagerMetaLinks($adsPageCount, $page, '/bazar/filtr/p/');

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/bazar/filtr')
                ->set('pagerpathpostfix', $httpQuery)
                ->set('pagecount', $adsPageCount)
                ->set('bftype', $type)
                ->set('bfsection', $section);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar');
    }

    /**
     * Show ad detail.
     *
     * @param string $uniquekey ad key
     */
    public function detail($uniquekey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $ad = \App\Model\AdvertisementModel::fetchActiveByKey($uniquekey);

        if ($ad === null) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->_willRenderActionView = false;
            self::redirect('/nenalezeno');
        }

        $this->_checkMetaData($layoutView, $ad);

        $view->set('ad', $ad)
                ->set('admessage', null);

        if (RequestMethods::post('submitAdReply')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/bazar/r/' . $ad->getUniqueKey());
            }

            $message = new \App\Model\AdMessageModel(array(
                'adId' => $ad->getId(),
                'msAuthor' => RequestMethods::post('name'),
                'msEmail' => RequestMethods::post('email'),
                'message' => RequestMethods::post('message'),
                'sendEmailCopy' => RequestMethods::post('getemailcopy', 0),
                'messageSent' => 0,
            ));

            if ($message->validate()) {
                try {
                    $data = array(
                        '{ADLINK}' => '<a href="http://' . $this->getServerHost() . '/bazar/r/' . $ad->getUniqueKey() . '">' . $ad->getTitle() . '</a>',
                        '{AUTHOR}' => $message->getMsAuthor(),
                        '{AUTHOREMAIL}' => $message->getMsEmail(),
                        '{MESSAGE}' => StringMethods::prepareEmailText($message->getMessage()),
                    );
                    $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('add-query', $data);

                    $mailer = new \THCFrame\Mailer\Mailer();
                    $mailer->setBody($emailTpl->getBody())
                            ->setSubject($emailTpl->getSubject())
                            ->setFrom('bazar@hastrman.cz');

                    if ($message->getSendEmailCopy() == 1) {
                        $mailer->setSendTo(array($message->getMsEmail(), $ad->getEmail()));
                    } else {
                        $mailer->setSendTo($ad->getEmail());
                    }

                    if ($mailer->send(true)) {
                        $message->messageSent = 1;
                        $messageId = $message->save();

                        Event::fire('app.log', array('success', 'Message with Id: ' . $messageId . ' send for Ad Id: ' . $ad->getId()));
                        $view->successMessage('Dotaz byl úspěšně odeslán');
                        self::redirect('/bazar/r/' . $ad->getUniqueKey());
                    } else {
                        Event::fire('app.log', array('fail', 'Email not send for Ad Id: ' . $ad->getId(),
                            'Error: ' . $message->getMessage(),));
                        $view->errorMessage('Chyba při odesílání emailu, opakujte akci později');
                        self::redirect('/bazar/r/' . $ad->getUniqueKey());
                    }
                } catch (\Exception $ex) {
                    \THCFrame\Core\Core::getLogger()->error($ex->getMessage());

                    Event::fire('app.log', array('fail', 'Email not send for Ad Id: ' . $ad->getId(),
                        'Error: ' . $ex->getMessage(),));
                    $view->errorMessage('Nepodařilo se odeslat dotaz k inzerátu, opakujte akci později');
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                }
            } else {
                Event::fire('app.log', array('fail', 'Errors: ' . json_encode($message->getErrors())));
                $view->set('errors', $message->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('admessage', $message);
            }
        }
    }

    /**
     * Search in ads.
     *
     * @param int $page
     */
    public function search($page = 1)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();
        $articlesPerPage = $this->getConfig()->search_results_per_page;

        if ($page <= 0) {
            $page = 1;
        }

        $requestUrl = 'http://' . $this->getServerHost() . '/doadsearch/' . $page;
        $parameters = array('adstr' => RequestMethods::get('adstr'));

        $request = new Request();
        $response = $request->request('post', $requestUrl, $parameters);
        $urls = json_decode($response, true);
        $articleCount = array_shift($urls);

        $searchPageCount = ceil($articleCount['totalCount'] / $articlesPerPage);

        $this->pagerMetaLinks($searchPageCount, $page, '/bazar/hledat/p/');

        $canonical = 'http://' . $this->getServerHost() . '/bazar/hledat';

        $view->set('result', $urls)
                ->set('currentpage', $page)
                ->set('pagecount', $searchPageCount)
                ->set('pagerpathprefix', '/bazar/hledat')
                ->set('pagerpathpostfix', '?' . http_build_query($parameters));

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar - Hledat');
    }

    /**
     * Create new ad.
     *
     * @before _secured, _member
     */
    public function add()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $canonical = 'http://' . $this->getServerHost() . '/bazar/pridat';
        $adSections = \App\Model\AdSectionModel::all(array('active = ?' => true));

        $view->set('adsections', $adSections)
                ->set('ad', null);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar - Nový inzerát');

        if (RequestMethods::post('submitAddAdvertisement')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/bazar');
            }

            $errors = $uploadErrors = array();
            $uniqueKey = sha1(RequestMethods::post('title') . RequestMethods::post('content') . $this->getUser()->getId());

            if (!$this->_checkAdKey($uniqueKey)) {
                $errors['title'] = array('Takovýto inzerát už nejspíše existuje');
            }

            $adTtl = $this->getConfig()->bazar_ad_ttl;
            $date = new \DateTime();
            $date->add(new \DateInterval('P' . (int) $adTtl . 'D'));
            $expirationDate = $date->format('Y-m-d');

            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

            $ad = new \App\Model\AdvertisementModel(array(
                'title' => RequestMethods::post('title'),
                'userId' => $this->getUser()->getId(),
                'sectionId' => RequestMethods::post('section'),
                'uniqueKey' => $uniqueKey,
                'adType' => RequestMethods::post('type'),
                'userAlias' => $this->getUser()->getWholeName(),
                'content' => RequestMethods::post('content'),
                'price' => RequestMethods::post('price', null),
                'expirationDate' => $expirationDate,
                'keywords' => $keywords,
                'state' => 0,
                'availabilityRequestToken' => '',
            ));

            if (empty($errors) && $ad->validate()) {
                $id = $ad->save();

                $fileManager = new FileManager(array(
                    'thumbWidth' => 230,
                    'thumbHeight' => 230,
                    'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                    'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                    'maxImageHeight' => $this->getConfig()->photo_maxheight,
                ));

                $userFolderName = $this->getUser()->getId() . '-' . StringMethods::createUrlKey($this->getUser()->getWholeName());
                $fileErrors = $fileManager->uploadImage('uploadfile', 'bazar/' . $userFolderName, time() . '_', true)->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($fileErrors)) {
                    $uploadErrors += $fileErrors;
                }

                if (!empty($files)) {
                    $files = array_slice($files, 0, 3);

                    foreach ($files as $i => $file) {
                        if ($file instanceof \THCFrame\Filesystem\Image) {
                            $adImage = new \App\Model\AdImageModel(array(
                                'adId' => $id,
                                'userId' => $this->getUser()->getId(),
                                'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                                'imgMain' => trim($file->getFilename(), '.'),
                                'imgThumb' => trim($file->getThumbname(), '.'),
                            ));

                            if ($adImage->validate()) {
                                $adImageId = $adImage->save();

                                if ($i == 0) {
                                    $ad->mainPhotoId = $adImageId;
                                    if ($ad->validate()) {
                                        $ad->save();
                                    }
                                }

                                Event::fire('app.log', array('success', 'Photo id: ' . $adImageId . ' in ad ' . $id));
                            } else {
                                Event::fire('app.log', array('fail', 'Upload photo for ad ' . $id,
                                    'Errors: ' . json_encode($adImage->getErrors()),));
                                $uploadErrors += $adImage->getErrors();
                            }
                        }
                    }

                    $errors['uploadfile'] = $uploadErrors;

                    if (empty($errors['uploadfile'])) {
                        Event::fire('app.log', array('success', 'Ad id: ' . $id));
                        $this->getCache()->erase('bazar');
                        $view->successMessage($this->lang('CREATE_SUCCESS'));
                        self::redirect('/bazar/r/' . $ad->getUniqueKey());
                    } else {
                        Event::fire('app.log', array('fail', 'Errors: ' . json_encode($errors + $ad->getErrors())));
                        $view->set('ad', $ad)
                                ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                                ->set('errors', $errors + $ad->getErrors());
                    }
                } else {
                    Event::fire('app.log', array('success', 'Ad id: ' . $id));
                    $this->getCache()->erase('bazar');
                    $view->successMessage('Inzerát' . $this->lang('CREATE_SUCCESS'));
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                }
            } else {
                Event::fire('app.log', array('fail', 'Errors: ' . json_encode($errors + $ad->getErrors())));
                $view->set('ad', $ad)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $errors + $ad->getErrors());
            }
        }
    }

    /**
     * Edit existing ad.
     *
     * @before _secured, _member
     *
     * @param string $uniqueKey ad key
     */
    public function edit($uniqueKey)
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        $canonical = 'http://' . $this->getServerHost() . '/bazar/upravit';
        $ad = \App\Model\AdvertisementModel::fetchAdByKeyUserId($uniqueKey, $this->getUser()->getId());

        if (null === $ad) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->_willRenderActionView = false;
            self::redirect('/bazar');
        }

        if ($ad->getState() == \App\Model\AdvertisementModel::STATE_SOLD) {
            $view->warningMessage($this->lang('AD_ALREADY_SOLD'));
            $this->_willRenderActionView = false;
            self::redirect('/bazar');
        }

        $adSections = \App\Model\AdSectionModel::all(array('active = ?' => true));

        $view->set('adsections', $adSections)
                ->set('ad', $ad);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar - Upravit inzerát');

        if (RequestMethods::post('submitEditAdvertisement')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/bazar');
            }

            $originalAd = clone $ad;
            $errors = $uploadErrors = array();
            $uniqueKey = sha1(RequestMethods::post('title') . RequestMethods::post('content') . $this->getUser()->getId());

            if ($ad->getUniqueKey() !== $uniqueKey && !$this->_checkAdKey($uniqueKey)) {
                $errors['title'] = array($this->lang('AD_ALREADY_EXISTS'));
            }

            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

            $ad->title = RequestMethods::post('title');
            $ad->uniqueKey = $uniqueKey;
            $ad->adType = RequestMethods::post('type');
            $ad->sectionId = RequestMethods::post('section');
            $ad->content = RequestMethods::post('content');
            $ad->price = RequestMethods::post('price', null);
            $ad->keywords = $keywords;

            if (empty($errors) && $ad->validate()) {
                $ad->save();

                $fileManager = new FileManager(array(
                    'thumbWidth' => 230,
                    'thumbHeight' => 230,
                    'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                    'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                    'maxImageHeight' => $this->getConfig()->photo_maxheight,
                ));

                $userFolderName = $this->getUser()->getId() . '-' . StringMethods::createUrlKey($this->getUser()->getWholeName());
                $fileErrors = $fileManager->uploadImage('uploadfile', 'bazar/' . $userFolderName, time() . '_', true)->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($fileErrors)) {
                    $uploadErrors += $fileErrors;
                }

                if (!empty($files)) {
                    $currentPhotoCount = \App\Model\AdImageModel::count(array('adId = ?' => $ad->getId()), array('id'));
                    $files = array_slice($files, 0, 3 - $currentPhotoCount);

                    if (!empty($files)) {
                        foreach ($files as $i => $file) {
                            if ($file instanceof \THCFrame\Filesystem\Image) {
                                $adImage = new \App\Model\AdImageModel(array(
                                    'adId' => $ad->getId(),
                                    'userId' => $this->getUser()->getId(),
                                    'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                                    'imgMain' => trim($file->getFilename(), '.'),
                                    'imgThumb' => trim($file->getThumbname(), '.'),
                                ));

                                if ($adImage->validate()) {
                                    $adImageId = $adImage->save();

                                    if ($i == 0 && empty($ad->mainPhotoId)) {
                                        $ad->mainPhotoId = $adImageId;
                                        if ($ad->validate()) {
                                            $ad->save();
                                        }
                                    }

                                    Event::fire('app.log', array('success', 'Photo id: ' . $adImageId . ' in ad ' . $ad->getId()));
                                } else {
                                    Event::fire('app.log', array('fail', 'Upload photo for ad ' . $ad->getId(),
                                        'Errors: ' . json_encode($adImage->getErrors()),));
                                    $uploadErrors += $adImage->getErrors();
                                }
                            }
                        }

                        $errors['uploadfile'] = $uploadErrors;

                        if (empty($errors['uploadfile'])) {
                            \App\Model\AdvertisementHistoryModel::logChanges($originalAd, $ad);
                            Event::fire('app.log', array('success', 'Ad id: ' . $ad->getId()));
                            $this->getCache()->erase('bazar');
                            $view->successMessage($this->lang('UPDATE_SUCCESS'));
                            self::redirect('/bazar/r/' . $ad->getUniqueKey());
                        } else {
                            Event::fire('app.log', array('fail',
                                'Errors: ' . json_encode($errors + $ad->getErrors()),));
                            $view->set('errors', $errors + $ad->getErrors());
                        }
                    } else {
                        \App\Model\AdvertisementHistoryModel::logChanges($originalAd, $ad);
                        Event::fire('app.log', array('success', 'Ad id: ' . $ad->getId()));
                        $this->getCache()->erase('bazar');
                        $view->successMessage($this->lang('UPDATE_SUCCESS') . ', ale více fotek už není možné nahrát');
                        self::redirect('/bazar/r/' . $ad->getUniqueKey());
                    }
                } else {
                    \App\Model\AdvertisementHistoryModel::logChanges($originalAd, $ad);
                    Event::fire('app.log', array('success', 'Ad id: ' . $ad->getId()));
                    $this->getCache()->erase('bazar');
                    $view->successMessage($this->lang('UPDATE_SUCCESS'));
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                }
            } else {
                Event::fire('app.log', array('fail', 'Ad id: ' . $ad->getId(),
                    'Errors: ' . json_encode($errors + $ad->getErrors()),));
                $view->set('errors', $errors + $ad->getErrors());
            }
        }
    }

    /**
     * Ajax Delete existing ad.
     *
     * @before _secured, _member
     *
     * @param string $uniqueKey ad key
     */
    public function ajaxDelete($uniqueKey)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $ad = \App\Model\AdvertisementModel::first(array('uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()));

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $adId = $ad->getId();

            $adImages = \App\Model\AdImageModel::all(array('adId = ?' => $adId));

            if ($adImages !== null) {
                foreach ($adImages as $image) {
                    $image->delete();
                }
            }

            if ($ad->delete()) {
                Event::fire('app.log', array('success', 'Ad id: ' . $adId));
                $this->getCache()->erase('bazar');
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('app.log', array('fail', 'Ad id: ' . $adId));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Delete existing ad.
     *
     * @before _secured, _member
     *
     * @param string $uniqueKey ad key
     */
    public function delete($uniqueKey)
    {
        $this->willRenderLayoutView = false;
        $view = $this->getActionView();

        $ad = \App\Model\AdvertisementModel::first(array('uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()));

        if (null === $ad) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/bazar');
        }

        $adId = $ad->getId();

        $adImages = \App\Model\AdImageModel::all(array('adId = ?' => $adId));

        if ($adImages !== null) {
            foreach ($adImages as $image) {
                $image->delete();
            }
        }

        if ($ad->delete()) {
            Event::fire('app.log', array('success', 'Ad id: ' . $adId));
            $this->getCache()->erase('bazar');
            $view->successMessage($this->lang('DELETE_SUCCESS'));
            self::redirect('/bazar');
        } else {
            Event::fire('app.log', array('fail', 'Ad id: ' . $adId));
            $view->warningMessage($this->lang('COMMON_FAIL'));
        }
    }

    /**
     * Delete ad image.
     *
     * @before _secured, _member
     *
     * @param int $id image id
     */
    public function deleteAdImage($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $adImage = \App\Model\AdImageModel::first(array('id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()));
        $ad = \App\Model\AdvertisementModel::first(array('id = ?' => $adImage->getAdId()));

        if ($adImage->getId() === $ad->getMainPhotoId()) {
            $ad->mainPhotoId = null;
        }

        if (null === $adImage) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($adImage->delete()) {
                $ad->save();
                $this->getCache()->erase('bazar');
                Event::fire('app.log', array('success', 'AdImage id: ' . $adImage->getId()));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('app.log', array('fail', 'AdImage id: ' . $adImage->getId()));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Get list of ads created by user currently logged id.
     *
     * @before _secured, _member
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
            $canonical = 'http://' . $this->getServerHost() . '/bazar/moje-inzeraty';
        } else {
            $canonical = 'http://' . $this->getServerHost() . '/bazar/moje-inzeraty/p/' . $page;
        }

        $ads = \App\Model\AdvertisementModel::fetchActiveByUser($userId, $adsPerPage, $page);
        $adsCount = \App\Model\AdvertisementModel::countActiveByUser($userId);

        $adsPageCount = ceil($adsCount / $adsPerPage);

        $this->pagerMetaLinks($adsPageCount, $page, '/bazar/moje-inzeraty/p/');

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/bazar/moje-inzeraty')
                ->set('pagecount', $adsPageCount);

        $layoutView->set('canonical', $canonical)
                ->set('metatitle', 'Hastrman - Bazar - Moje inzeráty');
    }

    /**
     * Create request for availability extend.
     *
     * @before _secured, _member
     *
     * @param string $uniqueKey ad key
     */
    public function extendAdExpiration($uniqueKey)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $view = $this->getActionView();

        $ad = \App\Model\AdvertisementModel::fetchAdByKeyUserId($uniqueKey, $this->getUser()->getId());

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $ad->hasAvailabilityRequest = true;

            if ($ad->validate()) {
                $ad->save();
                $view->successMessage($this->lang('AD_AVAILABILITY_REQUEST_SUCCESS'));
                Event::fire('app.log', array('success', 'Ad id: ' . $ad->getId()));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                $view->errorMessage($this->lang('COMMON_FAIL'));
                Event::fire('app.log', array('fail', 'Ad id: ' . $ad->getId(),
                    'Errors: ' . json_encode($ad->getErrors()),));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Create request for availability extend form alert email
     *
     * @param string $uniqueKey
     * @param string $token
     */
    public function extendAdExpirationFromEmail($uniqueKey, $token)
    {
        $view = $this->getActionView();
        $this->disableView();

        $ad = \App\Model\AdvertisementModel::first(
                        array('uniqueKey = ?' => $uniqueKey,
                            'availabilityRequestToken = ?' => $token,
                            'availabilityRequestTokenExpiration >= ?' => date('Y-m-d H:i:s')));

        if (null === $ad) {
            $view->warningMessage($this->lang('NOT_FOUND'));
        } else {
            $ad->hasAvailabilityRequest = true;
            $ad->availabilityRequestToken = '';
            $ad->availabilityRequestTokenExpiration = null;

            if ($ad->validate()) {
                $ad->save();
                Event::fire('app.log', array('success', 'Ad id: ' . $ad->getId()));
                $view->successMessage($this->lang('AD_AVAILABILITY_REQUEST_SUCCESS'));
            } else {
                Event::fire('app.log', array('fail', 'Ad id: ' . $ad->getId(),
                    'Errors: ' . json_encode($ad->getErrors()),));
                $view->errorMessage($this->lang('COMMON_FAIL'));
            }
        }

        self::redirect('/');
    }

    /**
     * Set advertisement new main photo
     *
     * @before _secured, _member
     *
     * @param integer $adId
     * @param integer $photoId
     */
    public function setNewMainPhoto($adId, $photoId)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $view = $this->getActionView();

        $ad = \App\Model\AdvertisementModel::first(array('id = ?' => (int) $adId));

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $adPhoto = \App\Model\AdImageModel::first(array('adId = ?' => (int) $adId, 'id = ?' => (int) $photoId));

            if (null === $adPhoto) {
                $this->ajaxResponse($this->lang('AD_PHOTO_NOT_FOUND'), true, 404);
            } else {
                $ad->mainPhotoId = $photoId;

                if ($ad->validate()) {
                    $ad->save();
                    $this->getCache()->erase('bazar');
                    $view->successMessage($this->lang('UPDATE_SUCCESS'));
                    Event::fire('app.log', array('success', 'Ad id: ' . $ad->getId() . ' new main photo: ' . $photoId));
                    $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, array('status' => 'active'));
                } else {
                    Event::fire('app.log', array('fail', 'Ad id: ' . $ad->getId() . ' new main photo: ' . $photoId,
                        'Errors: ' . json_encode($ad->getErrors()),));
                    $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
                }
            }
        }
    }

    /**
     *
     * @before _secured, _member
     *
     * @param string $uniqueKey ad key
     */
    public function setStateToSold($uniqueKey)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $view = $this->getActionView();

        $ad = \App\Model\AdvertisementModel::first(array('uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()));

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $ad->state = \App\Model\AdvertisementModel::STATE_SOLD;

            if ($ad->validate()) {
                $ad->save();
                $this->getCache()->erase('bazar');
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                Event::fire('app.log', array('success', 'Set state to sold for Ad id: ' . $ad->getId()));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('app.log', array('fail', 'Set state to sold for Ad id: ' . $ad->getId(),
                    'Errors: ' . json_encode($ad->getErrors()),));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

}
