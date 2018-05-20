<?php

namespace App\Controller;

use App\Etc\Controller;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Request\Request;
use THCFrame\View\View;

/**
 *
 */
class AdvertisementController extends Controller
{

    /**
     * Check if are set specific metadata or leave their default values.
     */
    private function _checkMetaData($layoutView, \App\Model\AdvertisementModel $object)
    {
        $uri = RequestMethods::server('REQUEST_URI');

        if ($object->getMetaTitle() == '') {
            $layoutView->set(View::META_TITLE, 'Hastrman - Bazar - ' . $object->getTitle());
        } else {
            $layoutView->set(View::META_TITLE, 'Hastrman - Bazar - ' . $object->getMetaTitle());
        }

        $canonical = $this->getServerHost() . '/bazar/r/' . $object->getUniqueKey();

        $layoutView->set(View::META_CANONICAL, $canonical)
                ->set('article', 1)
                ->set('articlecreated', $object->getCreated())
                ->set('articlemodified', $object->getModified())
                ->set('metaogurl', "{$this->getServerHost()}{$uri}")
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

        $adSections = \App\Model\AdSectionModel::all(['active = ?' => true]);

        $view->set('adsections', $adSections);

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/bazar';
        } else {
            $canonical = $this->getServerHost() . '/bazar/p/' . $page;
        }

        $content = $this->getCache()->get('bazar-' . $page);

        if (null !== $content) {
            $ads = $content;
        } else {
            $ads = \App\Model\AdvertisementModel::fetchAdsActive($adsPerPage, $page);

            $this->getCache()->set('bazar-' . $page, $ads);
        }

        $adsCount = \App\Model\AdvertisementModel::count(
                        ['active = ?' => true,
                            'expirationDate >= ?' => date('Y-m-d H:i:s'),]
        );

        $adsPageCount = ceil($adsCount / $adsPerPage);

        $this->pagerMetaLinks($adsPageCount, $page, '/bazar/p/');

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/bazar')
                ->set('pagecount', $adsPageCount);

        $layoutView->setBasicMeta('Hastrman - Bazar', $canonical);
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

        $adSections = \App\Model\AdSectionModel::all(['active = ?' => true]);
        $view->set('adsections', $adSections);

        $type = RequestMethods::get('bftype', '0');
        $section = RequestMethods::get('bfsection', '0');

        $httpQuery = '?' . http_build_query(['bftype' => $type, 'bfsection' => $section]);

        if ($page <= 0) {
            $page = 1;
        }

        if ($page == 1) {
            $canonical = $this->getServerHost() . '/bazar/filtr';
        } else {
            $canonical = $this->getServerHost() . '/bazar/filtr/p/' . $page;
        }

        if ($type == '0' && $section == '0') {
            $this->willRenderActionView = false;
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
                $this->willRenderActionView = false;
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

        $layoutView->setBasicMeta('Hastrman - Bazar', $canonical);
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
            $this->willRenderActionView = false;
            self::redirect('/nenalezeno');
        }

        $this->_checkMetaData($layoutView, $ad);

        $view->set('ad', $ad)
                ->set('admessage', null);

        if (RequestMethods::post('submitAdReply')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true ||
                    $this->checkMultiSubmissionProtectionToken() !== true ||
                    $this->checkBrowserAgentAndReferer()) {
                self::redirect('/bazar/r/' . $ad->getUniqueKey());
            }

            if (RequestMethods::post('url') !== '') {
                return;
            }

            $message = new \App\Model\AdMessageModel([
                'adId' => $ad->getId(),
                'msAuthor' => RequestMethods::post('name'),
                'msEmail' => RequestMethods::post('email'),
                'message' => RequestMethods::post('message'),
                'sendEmailCopy' => RequestMethods::post('getemailcopy', 0),
                'messageSent' => 0,
            ]);

            if ($message->validate()) {
                try {
                    $data = [
                        '{ADLINK}' => '<a href="' . $this->getServerHost() . '/bazar/r/' . $ad->getUniqueKey() . '">' . $ad->getTitle() . '</a>',
                        '{AUTHOR}' => $message->getMsAuthor(),
                        '{AUTHOREMAIL}' => $message->getMsEmail(),
                        '{MESSAGE}' => StringMethods::prepareEmailText($message->getMessage()),
                    ];
                    $emailTpl = \Admin\Model\EmailModel::loadAndPrepare('add-query', $data);

                    if ($emailTpl !== null) {
                        $mailer = new \THCFrame\Mailer\Mailer();
                        $mailer->setBody($emailTpl->getBody())
                                ->setSubject($emailTpl->getSubject())
                                ->setFrom('bazar@hastrman.cz');

                        if ($message->getSendEmailCopy() == 1) {
                            $mailer->setSendTo([$message->getMsEmail(), $ad->getEmail()]);
                        } else {
                            $mailer->setSendTo($ad->getEmail());
                        }

                        if ($mailer->send(true)) {
                            $message->messageSent = 1;
                            $messageId = $message->save();

                            Event::fire('app.log', ['success', 'Message with Id: ' . $messageId . ' send for Ad Id: ' . $ad->getId()]);
                            $view->successMessage('Dotaz byl úspěšně odeslán');
                        } else {
                            Event::fire('app.log', ['fail', 'Email not send for Ad Id: ' . $ad->getId(),
                                'Error: ' . $message->getMessage(),]);
                            $view->errorMessage('Chyba při odesílání emailu, opakujte akci později');
                        }
                    } else {
                        Event::fire('app.log', ['fail', 'Email template not found']);
                        $view->errorMessage('Chyba při odesílání emailu, opakujte akci později');
                    }
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                } catch (\Exception $ex) {
                    \THCFrame\Core\Core::getLogger()->error($ex->getMessage());

                    Event::fire('app.log', ['fail', 'Email not send for Ad Id: ' . $ad->getId(),
                        'Error: ' . $ex->getMessage(),]);
                    $view->errorMessage('Nepodařilo se odeslat dotaz k inzerátu, opakujte akci později');
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                }
            } else {
                Event::fire('app.log', ['fail', 'Errors: ' . json_encode($message->getErrors())]);
                $view->set('errors', $message->getErrors())
                        ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
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

        $searchString = RequestMethods::get('adstr');

        if (empty($searchString)) {
            $view->warningMessage('Musíte zadate text, který chcete v bazaru vyhledat');
            self::redirect('/bazar');
        }

        $requestUrl = $this->getServerHost() . '/doadsearch/' . $page;
        $parameters = ['adstr' => $searchString];

        $request = new Request();
        $response = $request->request('post', $requestUrl, $parameters);
        $urls = json_decode($response, true);
        $articleCount = array_shift($urls);

        $searchPageCount = ceil($articleCount['totalCount'] / $articlesPerPage);

        $this->pagerMetaLinks($searchPageCount, $page, '/bazar/hledat/p/');

        $canonical = $this->getServerHost() . '/bazar/hledat';

        $view->set('result', $urls)
                ->set('currentpage', $page)
                ->set('pagecount', $searchPageCount)
                ->set('pagerpathprefix', '/bazar/hledat')
                ->set('pagerpathpostfix', '?' . http_build_query($parameters));

        $layoutView->setBasicMeta('Hastrman - Bazar - Hledat', $canonical);
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

        $canonical = $this->getServerHost() . '/bazar/pridat';
        $adSections = \App\Model\AdSectionModel::all(['active = ?' => true]);

        $view->set('adsections', $adSections)
                ->set('ad', null);

        $layoutView->setBasicMeta('Hastrman - Bazar - Nový inzerát', $canonical);

        if (RequestMethods::post('submitAddAdvertisement')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true ||
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/bazar');
            }

            $errors = $uploadErrors = [];
            $uniqueKey = sha1(RequestMethods::post('title') . RequestMethods::post('content') . $this->getUser()->getId());

            if (!\App\Model\AdvertisementModel::checkAdKey($uniqueKey)) {
                $errors['title'] = ['Takovýto inzerát už nejspíše existuje'];
            }

            $adTtl = $this->getConfig()->bazar_ad_ttl;
            $date = new \DateTime();
            $date->add(new \DateInterval('P' . (int) $adTtl . 'D'));
            $expirationDate = $date->format('Y-m-d');

            $keywords = strtolower(StringMethods::removeDiacriticalMarks(RequestMethods::post('keywords')));

            $ad = new \App\Model\AdvertisementModel([
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
            ]);

            if (empty($errors) && $ad->validate()) {
                $id = $ad->save();

                $fileManager = new FileManager([
                    'thumbWidth' => 230,
                    'thumbHeight' => 230,
                    'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                    'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                    'maxImageHeight' => $this->getConfig()->photo_maxheight,
                ]);

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
                            $adImage = new \App\Model\AdImageModel([
                                'adId' => $id,
                                'userId' => $this->getUser()->getId(),
                                'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                                'imgMain' => trim($file->getFilename(), '.'),
                                'imgThumb' => trim($file->getThumbname(), '.'),
                            ]);

                            if ($adImage->validate()) {
                                $adImageId = $adImage->save();

                                if ($i == 0) {
                                    $ad->mainPhotoId = $adImageId;
                                    if ($ad->validate()) {
                                        $ad->save();
                                    }
                                }

                                Event::fire('app.log', ['success', 'Photo id: ' . $adImageId . ' in ad ' . $id]);
                            } else {
                                Event::fire('app.log', ['fail', 'Upload photo for ad ' . $id,
                                    'Errors: ' . json_encode($adImage->getErrors()),]);
                                $uploadErrors += $adImage->getErrors();
                            }
                        }
                    }

                    $errors['uploadfile'] = $uploadErrors;

                    if (empty($errors['uploadfile'])) {
                        Event::fire('app.log', ['success', 'Ad id: ' . $id]);
                        $this->getCache()->erase('bazar');
                        $view->successMessage($this->lang('CREATE_SUCCESS'));
                        self::redirect('/bazar/r/' . $ad->getUniqueKey());
                    } else {
                        Event::fire('app.log', ['fail', 'Errors: ' . json_encode($errors + $ad->getErrors())]);
                        $view->set('ad', $ad)
                                ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                                ->set('errors', $errors + $ad->getErrors());
                    }
                } else {
                    Event::fire('app.log', ['success', 'Ad id: ' . $id]);
                    $this->getCache()->erase('bazar');
                    $view->successMessage('Inzerát' . $this->lang('CREATE_SUCCESS'));
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                }
            } else {
                Event::fire('app.log', ['fail', 'Errors: ' . json_encode($errors + $ad->getErrors())]);
                $view->set('ad', $ad)
                        ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
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

        $canonical = $this->getServerHost() . '/bazar/upravit';
        $ad = \App\Model\AdvertisementModel::fetchAdByKeyUserId($uniqueKey, $this->getUser()->getId());

        if (null === $ad) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/bazar');
        }

        if ($ad->getState() == \App\Model\AdvertisementModel::STATE_SOLD) {
            $view->warningMessage($this->lang('AD_ALREADY_SOLD'));
            $this->willRenderActionView = false;
            self::redirect('/bazar');
        }

        $adSections = \App\Model\AdSectionModel::all(['active = ?' => true]);

        $view->set('adsections', $adSections)
                ->set('ad', $ad);

        $layoutView->setBasicMeta('Hastrman - Bazar - Upravit inzerát', $canonical);

        if (RequestMethods::post('submitEditAdvertisement')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true ||
                    $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/bazar');
            }

            $originalAd = clone $ad;
            $errors = $uploadErrors = [];
            $uniqueKey = sha1(RequestMethods::post('title') . RequestMethods::post('content') . $this->getUser()->getId());

            if ($ad->getUniqueKey() !== $uniqueKey && !\App\Model\AdvertisementModel::checkAdKey($uniqueKey)) {
                $errors['title'] = [$this->lang('AD_ALREADY_EXISTS')];
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

                $fileManager = new FileManager([
                    'thumbWidth' => 230,
                    'thumbHeight' => 230,
                    'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                    'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                    'maxImageHeight' => $this->getConfig()->photo_maxheight,
                ]);

                $userFolderName = $this->getUser()->getId() . '-' . StringMethods::createUrlKey($this->getUser()->getWholeName());
                $fileErrors = $fileManager->uploadImage('uploadfile', 'bazar/' . $userFolderName, time() . '_', true)->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($fileErrors)) {
                    $uploadErrors += $fileErrors;
                }

                if (!empty($files)) {
                    $currentPhotoCount = \App\Model\AdImageModel::count(['adId = ?' => $ad->getId()], ['id']);
                    $files = array_slice($files, 0, 3 - $currentPhotoCount);

                    if (!empty($files)) {
                        foreach ($files as $i => $file) {
                            if ($file instanceof \THCFrame\Filesystem\Image) {
                                $adImage = new \App\Model\AdImageModel([
                                    'adId' => $ad->getId(),
                                    'userId' => $this->getUser()->getId(),
                                    'photoName' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                                    'imgMain' => trim($file->getFilename(), '.'),
                                    'imgThumb' => trim($file->getThumbname(), '.'),
                                ]);

                                if ($adImage->validate()) {
                                    $adImageId = $adImage->save();

                                    if ($i == 0 && empty($ad->mainPhotoId)) {
                                        $ad->mainPhotoId = $adImageId;
                                        if ($ad->validate()) {
                                            $ad->save();
                                        }
                                    }

                                    Event::fire('app.log', ['success', 'Photo id: ' . $adImageId . ' in ad ' . $ad->getId()]);
                                } else {
                                    Event::fire('app.log', ['fail', 'Upload photo for ad ' . $ad->getId(),
                                        'Errors: ' . json_encode($adImage->getErrors()),]);
                                    $uploadErrors += $adImage->getErrors();
                                }
                            }
                        }

                        $errors['uploadfile'] = $uploadErrors;

                        if (empty($errors['uploadfile'])) {
                            \App\Model\AdvertisementHistoryModel::logChanges($originalAd, $ad);
                            Event::fire('app.log', ['success', 'Ad id: ' . $ad->getId()]);
                            $this->getCache()->erase('bazar');
                            $view->successMessage($this->lang('UPDATE_SUCCESS'));
                            self::redirect('/bazar/r/' . $ad->getUniqueKey());
                        } else {
                            Event::fire('app.log', ['fail',
                                'Errors: ' . json_encode($errors + $ad->getErrors()),]);
                            $view->set('errors', $errors + $ad->getErrors());
                        }
                    } else {
                        \App\Model\AdvertisementHistoryModel::logChanges($originalAd, $ad);
                        Event::fire('app.log', ['success', 'Ad id: ' . $ad->getId()]);
                        $this->getCache()->erase('bazar');
                        $view->successMessage($this->lang('UPDATE_SUCCESS') . ', ale více fotek už není možné nahrát');
                        self::redirect('/bazar/r/' . $ad->getUniqueKey());
                    }
                } else {
                    \App\Model\AdvertisementHistoryModel::logChanges($originalAd, $ad);
                    Event::fire('app.log', ['success', 'Ad id: ' . $ad->getId()]);
                    $this->getCache()->erase('bazar');
                    $view->successMessage($this->lang('UPDATE_SUCCESS'));
                    self::redirect('/bazar/r/' . $ad->getUniqueKey());
                }
            } else {
                Event::fire('app.log', ['fail', 'Ad id: ' . $ad->getId(),
                    'Errors: ' . json_encode($errors + $ad->getErrors()),]);
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

        $ad = \App\Model\AdvertisementModel::first(['uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()]);

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $adId = $ad->getId();

            $adImages = \App\Model\AdImageModel::all(['adId = ?' => $adId]);

            if ($adImages !== null) {
                foreach ($adImages as $image) {
                    $image->delete();
                }
            }

            if ($ad->delete()) {
                Event::fire('app.log', ['success', 'Ad id: ' . $adId]);
                $this->getCache()->erase('bazar');
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('app.log', ['fail', 'Ad id: ' . $adId]);
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

        $ad = \App\Model\AdvertisementModel::first(['uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()]);

        if (null === $ad) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/bazar');
        }

        $adId = $ad->getId();

        $adImages = \App\Model\AdImageModel::all(['adId = ?' => $adId]);

        if ($adImages !== null) {
            foreach ($adImages as $image) {
                $image->delete();
            }
        }

        if ($ad->delete()) {
            Event::fire('app.log', ['success', 'Ad id: ' . $adId]);
            $this->getCache()->erase('bazar');
            $view->successMessage($this->lang('DELETE_SUCCESS'));
            self::redirect('/bazar');
        } else {
            Event::fire('app.log', ['fail', 'Ad id: ' . $adId]);
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

        $adImage = \App\Model\AdImageModel::first(['id = ?' => (int) $id, 'userId = ?' => $this->getUser()->getId()]);
        $ad = \App\Model\AdvertisementModel::first(['id = ?' => $adImage->getAdId()]);

        if ($adImage->getId() === $ad->getMainPhotoId()) {
            $ad->mainPhotoId = null;
        }

        if (null === $adImage) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($adImage->delete()) {
                $ad->save();
                $this->getCache()->erase('bazar');
                Event::fire('app.log', ['success', 'AdImage id: ' . $adImage->getId()]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('app.log', ['fail', 'AdImage id: ' . $adImage->getId()]);
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
            $canonical = $this->getServerHost() . '/bazar/moje-inzeraty';
        } else {
            $canonical = $this->getServerHost() . '/bazar/moje-inzeraty/p/' . $page;
        }

        $ads = \App\Model\AdvertisementModel::fetchActiveByUser($userId, $adsPerPage, $page);
        $adsCount = \App\Model\AdvertisementModel::countActiveByUser($userId);

        $adsPageCount = ceil($adsCount / $adsPerPage);

        $this->pagerMetaLinks($adsPageCount, $page, '/bazar/moje-inzeraty/p/');

        $view->set('ads', $ads)
                ->set('currentpage', $page)
                ->set('pagerpathprefix', '/bazar/moje-inzeraty')
                ->set('pagecount', $adsPageCount);

        $layoutView->setBasicMeta('Hastrman - Bazar - Moje inzeráty', $canonical);
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
                Event::fire('app.log', ['success', 'Ad id: ' . $ad->getId()]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                $view->errorMessage($this->lang('COMMON_FAIL'));
                Event::fire('app.log', ['fail', 'Ad id: ' . $ad->getId(),
                    'Errors: ' . json_encode($ad->getErrors()),]);
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
                        ['uniqueKey = ?' => $uniqueKey,
                            'availabilityRequestToken = ?' => $token,
                            'availabilityRequestTokenExpiration >= ?' => date('Y-m-d H:i:s')]);

        if (null === $ad) {
            $view->warningMessage($this->lang('NOT_FOUND'));
        } else {
            $ad->hasAvailabilityRequest = true;
            $ad->availabilityRequestToken = '';
            $ad->availabilityRequestTokenExpiration = null;

            if ($ad->validate()) {
                $ad->save();
                Event::fire('app.log', ['success', 'Ad id: ' . $ad->getId()]);
                $view->successMessage($this->lang('AD_AVAILABILITY_REQUEST_SUCCESS'));
            } else {
                Event::fire('app.log', ['fail', 'Ad id: ' . $ad->getId(),
                    'Errors: ' . json_encode($ad->getErrors()),]);
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

        $ad = \App\Model\AdvertisementModel::first(['id = ?' => (int) $adId]);

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $adPhoto = \App\Model\AdImageModel::first(['adId = ?' => (int) $adId, 'id = ?' => (int) $photoId]);

            if (null === $adPhoto) {
                $this->ajaxResponse($this->lang('AD_PHOTO_NOT_FOUND'), true, 404);
            } else {
                $ad->mainPhotoId = $photoId;

                if ($ad->validate()) {
                    $ad->save();
                    $this->getCache()->erase('bazar');
                    $view->successMessage($this->lang('UPDATE_SUCCESS'));
                    Event::fire('app.log', ['success', 'Ad id: ' . $ad->getId() . ' new main photo: ' . $photoId]);
                    $this->ajaxResponse($this->lang('COMMON_SUCCESS'), false, 200, ['status' => 'active']);
                } else {
                    Event::fire('app.log', ['fail', 'Ad id: ' . $ad->getId() . ' new main photo: ' . $photoId,
                        'Errors: ' . json_encode($ad->getErrors()),]);
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

        $ad = \App\Model\AdvertisementModel::first(['uniqueKey = ?' => $uniqueKey, 'userId = ?' => $this->getUser()->getId()]);

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $ad->state = \App\Model\AdvertisementModel::STATE_SOLD;
            $ad->active = 0;

            if ($ad->validate()) {
                $ad->save();
                $this->getCache()->erase('bazar');
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                Event::fire('app.log', ['success', 'Set state to sold for Ad id: ' . $ad->getId()]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('app.log', ['fail', 'Set state to sold for Ad id: ' . $ad->getId(),
                    'Errors: ' . json_encode($ad->getErrors()),]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

}
