<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use App\Model\AdImageModel;
use App\Model\AdSectionModel;
use App\Model\AdvertisementModel;
use DateInterval;
use DateTime;
use Exception;
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
class AdvertisementController extends Controller
{

    /**
     * Get list of all advertisements.
     *
     * @before _secured, _participant
     * @throws Data
     */
    public function index(): void
    {
        $view = $this->getActionView();
        $ads = AdvertisementModel::fetchAll();
        $view->set('ads', $ads);
    }

    /**
     * Get list of advertisement sections.
     *
     * @before _secured, _participant
     * @throws Data
     */
    public function sections(): void
    {
        $view = $this->getActionView();
        $adsections = AdSectionModel::fetchAll();
        $view->set('adsections', $adsections);
    }

    /**
     * Show detail of existing ad.
     *
     * @before _secured, _participant
     *
     * @param int $id ad id
     * @throws Data
     */
    public function detail($id): void
    {
        $view = $this->getActionView();
        $ad = AdvertisementModel::fetchById($id);

        if (null === $ad) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/advertisement/');
        }

        $view->set('ad', $ad);
    }

    /**
     * Delete existing ad.
     *
     * @before _secured, _admin
     *
     * @param int $id ad id
     * @throws Connector
     * @throws Implementation
     */
    public function delete($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $ad = AdvertisementModel::first(
            ['id = ?' => (int)$id], ['id']
        );

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $adImages = AdImageModel::all(['adId = ?' => $ad->getId()]);

            if ($adImages !== null) {
                foreach ($adImages as $image) {
                    $image->delete();
                }
            }

            if ($ad->delete()) {
                $this->getCache()->erase('bazar-');
                Event::fire('admin.log', ['success', 'Ad id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Ad id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Change ad state (active/inactive).
     *
     * @before _secured, _admin
     *
     * @param int $id ad id
     * @throws Connector
     * @throws Implementation
     */
    public function changeState($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $ad = AdvertisementModel::first(['id = ?' => (int)$id]);

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($ad->active) {
                $ad->active = 0;
            } else {
                $ad->active = 1;
            }

            if ($ad->validate()) {
                $ad->save();

                $this->getCache()->erase('bazar-');
                Event::fire('admin.log', ['success', 'Ad id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Ad id: ' . $id,
                    'Errors: ' . json_encode($ad->getErrors()),
                ]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Delete image from ad.
     *
     * @before _secured, _admin
     *
     * @param int $imageId image id
     * @throws Connector
     * @throws Implementation
     */
    public function deleteAdImage($imageId): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        /** @var AdImageModel $adImage */
        $adImage = AdImageModel::first(['id = ?' => (int)$imageId]);
        /** @var AdvertisementModel $ad */
        $ad = AdvertisementModel::first(['id = ?' => $adImage->getAdId()]);

        if ($adImage->getId() === $ad->getMainPhotoId()) {
            $ad->setMainPhotoId(null);
        }

        if (null === $adImage) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } elseif ($adImage->delete()) {
            $this->getCache()->erase('bazar-');
            Event::fire('admin.log', [
                'success',
                'Ad image id: ' . $imageId
                . ' from ad: ' . $adImage->getAdId(),
            ]);
            $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
        } else {
            Event::fire('admin.log', [
                'fail',
                'Ad image id: ' . $imageId
                . ' from ad: ' . $adImage->getAdId(),
            ]);
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
        }
    }

    /**
     * Create new section for advertisements.
     *
     * @before _secured, _admin
     * @throws Data
     * @throws Connector
     * @throws Implementation
     * @throws Validation
     */
    public function addSection(): void
    {
        $view = $this->getActionView();

        $view->set('adsection', null);

        if (RequestMethods::post('submitAddAdSection')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/advertisement/sections/');
            }

            $errors = [];
            $urlKey = StringMethods::createUrlKey(RequestMethods::post('title'));

            if (!AdSectionModel::checkUrlKey($urlKey)) {
                $errors['title'] = [$this->lang('ARTICLE_TITLE_IS_USED')];
            }

            $adsection = new AdSectionModel([
                'title' => RequestMethods::post('title'),
                'urlKey' => $urlKey,
            ]);

            if (empty($errors) && $adsection->validate()) {
                $id = $adsection->save();

                Event::fire('admin.log', ['success', 'AdSection id: ' . $id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/advertisement/sections/');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($errors + $adsection->getErrors())]);
                $view->set('adsection', $adsection)
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('errors', $errors + $adsection->getErrors());
            }
        }
    }

    /**
     * Edit existing advertisement section.
     *
     * @before _secured, _admin
     *
     * @param int $id section id
     * @throws Data
     * @throws Connector
     * @throws Implementation
     */
    public function editSection($id): void
    {
        $view = $this->getActionView();

        $adsection = AdSectionModel::first(['id = ?' => (int)$id]);

        if (null === $adsection) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/advertisement/sections/');
        }

        $view->set('adsection', $adsection);

        if (RequestMethods::post('submitEditAdSection')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/advertisement/sections/');
            }

            $errors = [];
            $urlKey = StringMethods::createUrlKey(RequestMethods::post('title'));

            if ($adsection->getUrlKey() !== $urlKey && !AdSectionModel::checkUrlKey($urlKey)) {
                $errors['title'] = [$this->lang('ARTICLE_TITLE_IS_USED')];
            }

            $adsection->title = RequestMethods::post('title');
            $adsection->urlKey = $urlKey;
            $adsection->active = RequestMethods::post('active');

            if (empty($errors) && $adsection->validate()) {
                $adsection->save();

                $this->getCache()->erase('bazar-');
                Event::fire('admin.log', ['success', 'AdSection id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/advertisement/sections/');
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'AdSection id: ' . $id,
                    'Errors: ' . json_encode($errors + $adsection->getErrors()),
                ]);
                $view->set('errors', $errors + $adsection->getErrors());
            }
        }
    }

    /**
     * Delete existing advertisement section.
     *
     * @before _secured, _admin
     *
     * @param int $id section id
     * @throws Connector
     * @throws Implementation
     */
    public function deleteSection($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $adsection = AdSectionModel::first(
            ['id = ?' => (int)$id], ['id']
        );

        if (null === $adsection) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } elseif ($adsection->delete()) {
            $this->getCache()->erase('bazar-');
            Event::fire('admin.log', ['success', 'AdSection id: ' . $id]);
            $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
        } else {
            Event::fire('admin.log', [
                'fail',
                'AdSection id: ' . $id,
                'Errors: ' . json_encode($adsection->getErrors()),
            ]);
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
        }
    }

    /**
     * Extend ad availability for specific amount of days.
     *
     * @before _secured, _admin
     *
     * @param int $id ad id
     * @throws Exception
     */
    public function extendAvailability($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $ad = AdvertisementModel::first(['id = ?' => (int)$id, 'hasAvailabilityRequest = ?' => true]);

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $adTtl = $this->getConfig()->bazar_ad_ttl;

            $date = new DateTime();
            $date->add(new DateInterval('P' . (int)$adTtl . 'D'));
            $expirationDate = $date->format('Y-m-d');

            $ad->hasAvailabilityRequest = false;
            $ad->expirationDate = $expirationDate;

            if ($ad->validate()) {
                $ad->save();

                $this->getCache()->erase('bazar-');
                Event::fire('admin.log', ['success', 'Ad id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Ad id: ' . $id,
                    'Errors: ' . json_encode($ad->getErrors()),
                ]);
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Response for ajax call from datatables plugin.
     *
     * @before _secured, _participant
     */
    public function load(): void
    {
        $this->disableView();
        $maxRows = 100;

        $page = (int)RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "adv.created LIKE '%%?%%' OR adv.userAlias LIKE '%%?%%' OR adv.title LIKE '%%?%%'";

            $query = AdvertisementModel::getQuery(
                [
                    'adv.id',
                    'adv.userId',
                    'adv.userAlias',
                    'adv.title',
                    'adv.active',
                    'adv.state',
                    'adv.expirationDate',
                    'adv.created',
                ])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->wheresql($whereCond, $search, $search, $search);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('adv.id', $dir);
                } elseif ($column == 1) {
                    $query->order('adv.title', $dir);
                } elseif ($column == 2) {
                    $query->order('adv.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('adv.created', $dir);
                }
            } else {
                $query->order('adv.id', 'desc');
            }

            $limit = min((int)RequestMethods::post('iDisplayLength'), $maxRows);
            $query->limit($limit, $page + 1);
            $ads = AdvertisementModel::initialize($query);

            $countQuery = AdvertisementModel::getQuery(['adv.id'])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->wheresql($whereCond, $search, $search, $search);

            $adsCount = AdvertisementModel::initialize($countQuery);
            unset($countQuery);
            $count = count($adsCount);
            unset($adsCount);
        } else {
            $query = AdvertisementModel::getQuery(
                [
                    'adv.id',
                    'adv.userId',
                    'adv.userAlias',
                    'adv.title',
                    'adv.active',
                    'adv.state',
                    'adv.expirationDate',
                    'adv.created',
                ])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

            if (RequestMethods::issetpost('iSortCol_0')) {
                $dir = RequestMethods::issetpost('sSortDir_0') ? RequestMethods::post('sSortDir_0') : 'asc';
                $column = RequestMethods::post('iSortCol_0');

                if ($column == 0) {
                    $query->order('adv.id', $dir);
                } elseif ($column == 1) {
                    $query->order('adv.title', $dir);
                } elseif ($column == 2) {
                    $query->order('adv.userAlias', $dir);
                } elseif ($column == 3) {
                    $query->order('adv.created', $dir);
                }
            } else {
                $query->order('adv.id', 'desc');
            }

            $limit = min((int)RequestMethods::post('iDisplayLength'), $maxRows);
            $query->limit($limit, $page + 1);
            $ads = AdvertisementModel::initialize($query);
            $count = AdvertisementModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = [];
        if (null !== $ads) {
            foreach ($ads as $ad) {
                $label = '';
                if ($ad->active) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelRed'>Neaktivní</span>";
                }

                if ($ad->state == AdvertisementModel::STATE_SOLD) {
                    $label .= "<span class='infoLabel infoLabelOrange'>Prodáno</span>";
                }

                $arr = [];
                $arr [] = '[ "' . $ad->getId() . '"';
                $arr [] = '"' . htmlentities($ad->getTitle()) . '"';
                $arr [] = '"' . $ad->getUserAlias() . '"';
                $arr [] = '"' . $ad->getExpirationDate() . '"';
                $arr [] = '"' . $ad->getCreated() . '"';
                $arr [] = '"' . $label . '"';

                $tempStr = '"';
                if ($this->isAdmin()) {
                    $tempStr .= "<a href='/admin/advertisement/delete/" . $ad->getId() . "' class='btn btn3 btn_trash ajaxDelete' title='Smazat'></a>";
                }
                if ($this->isAdmin() || $ad->getUserId() == $this->getUser()->getId()) {
                    $tempStr .= "<a href='/admin/advertisement/detail/" . $ad->getId() . "' class='btn btn3 btn_search' title='Detail'></a>";
                }

                if ($this->isAdmin() && $ad->getHasAvailabilityRequest()) {
                    $tempStr .= "<a href='/admin/advertisement/extendavailability/" . $ad->getId() . "' class='btn btn3 btn_refresh ajaxReload' title='Prodloužit životnost'></a>";
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

}
