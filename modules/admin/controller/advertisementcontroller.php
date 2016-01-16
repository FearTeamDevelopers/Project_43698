<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;

/**
 *
 */
class AdvertisementController extends Controller
{

    /**
     * Check whether unique ad section identifier already exist or not.
     *
     * @param string $key
     *
     * @return bool
     */
    private function _checkSectionUrlKey($key)
    {
        $status = \App\Model\AdSectionModel::first(array('urlKey = ?' => $key));

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get list of all advertisements.
     *
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();
        $ads = \App\Model\AdvertisementModel::fetchAll();
        $view->set('ads', $ads);
    }

    /**
     * Get list of advertisement sections.
     *
     * @before _secured, _participant
     */
    public function sections()
    {
        $view = $this->getActionView();
        $adsections = \App\Model\AdSectionModel::fetchAll();
        $view->set('adsections', $adsections);
    }

    /**
     * Show detail of existing ad.
     *
     * @before _secured, _participant
     *
     * @param int $id ad id
     */
    public function detail($id)
    {
        $view = $this->getActionView();
        $ad = \App\Model\AdvertisementModel::fetchById($id);

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
     */
    public function delete($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $ad = \App\Model\AdvertisementModel::first(
                        array('id = ?' => (int) $id), array('id')
        );

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $adImages = \App\Model\AdImageModel::all(array('adId = ?' => $ad->getId()));

            if ($adImages !== null) {
                foreach ($adImages as $image) {
                    $image->delete();
                }
            }

            if ($ad->delete()) {
                $this->getCache()->erase('bazar-');
                Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $id));
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
     */
    public function changeState($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $ad = \App\Model\AdvertisementModel::first(array('id = ?' => (int) $id));

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
                Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $id,
                    'Errors: ' . json_encode($ad->getErrors())));
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
     */
    public function deleteAdImage($imageId)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $adImage = \App\Model\AdImageModel::first(array('id = ?' => (int) $imageId));
        $ad = \App\Model\AdvertisementModel::first(array('id = ?' => $adImage->getAdId()));

        if ($adImage->getId() === $ad->getMainPhotoId()) {
            $ad->mainPhotoId = null;
        }

        if (null === $adImage) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($adImage->delete()) {
                $this->getCache()->erase('bazar-');
                Event::fire('admin.log',
                        array('success', 'Ad image id: ' . $imageId
                    . ' from ad: ' . $adImage->getAdId(),));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log',
                        array('fail', 'Ad image id: ' . $imageId
                    . ' from ad: ' . $adImage->getAdId(),));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Create new section for advertisements.
     *
     * @before _secured, _admin
     */
    public function addSection()
    {
        $view = $this->getActionView();

        $view->set('adsection', null);

        if (RequestMethods::post('submitAddAdSection')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->_checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/admin/advertisement/sections/');
            }

            $errors = array();
            $urlKey = $this->createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkSectionUrlKey($urlKey)) {
                $errors['title'] = array($this->lang('ARTICLE_TITLE_IS_USED'));
            }

            $adsection = new \App\Model\AdSectionModel(array(
                'title' => RequestMethods::post('title'),
                'urlKey' => $urlKey,
            ));

            if (empty($errors) && $adsection->validate()) {
                $id = $adsection->save();

                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/advertisement/sections/');
            } else {
                Event::fire('admin.log', array('fail', 'Errors: ' . json_encode($errors + $adsection->getErrors())));
                $view->set('adsection', $adsection)
                        ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
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
     */
    public function editSection($id)
    {
        $view = $this->getActionView();

        $adsection = \App\Model\AdSectionModel::first(array('id = ?' => (int) $id));

        if (null === $adsection) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/advertisement/sections/');
        }

        $view->set('adsection', $adsection);

        if (RequestMethods::post('submitEditAdSection')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/advertisement/sections/');
            }

            $errors = array();
            $urlKey = $this->createUrlKey(RequestMethods::post('title'));

            if ($adsection->getUrlKey() !== $urlKey && !$this->_checkSectionUrlKey($urlKey)) {
                $errors['title'] = array($this->lang('ARTICLE_TITLE_IS_USED'));
            }

            $adsection->title = RequestMethods::post('title');
            $adsection->urlKey = $urlKey;
            $adsection->active = RequestMethods::post('active');

            if (empty($errors) && $adsection->validate()) {
                $adsection->save();

                $this->getCache()->erase('bazar-');
                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/advertisement/sections/');
            } else {
                Event::fire('admin.log', array('fail', 'AdSection id: ' . $id,
                    'Errors: ' . json_encode($errors + $adsection->getErrors()),));
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
     */
    public function deleteSection($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $adsection = \App\Model\AdSectionModel::first(
                        array('id = ?' => (int) $id), array('id')
        );

        if (null === $adsection) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($adsection->delete()) {
                $this->getCache()->erase('bazar-');
                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', array('fail', 'AdSection id: ' . $id,
                    'Errors: ' . json_encode($adsection->getErrors()),));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Extend ad availability for specific amount of days.
     *
     * @before _secured, _admin
     *
     * @param int $id ad id
     */
    public function extendAvailability($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $ad = \App\Model\AdvertisementModel::first(array('id = ?' => (int) $id, 'hasAvailabilityRequest = ?' => true));

        if (null === $ad) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            $adTtl = $this->getConfig()->bazar_ad_ttl;

            $date = new \DateTime();
            $date->add(new \DateInterval('P' . (int) $adTtl . 'D'));
            $expirationDate = $date->format('Y-m-d');

            $ad->hasAvailabilityRequest = false;
            $ad->expirationDate = $expirationDate;

            if ($ad->validate()) {
                $ad->save();

                $this->getCache()->erase('bazar-');
                Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $id,
                    'Errors: ' . json_encode($ad->getErrors()),));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Response for ajax call from datatables plugin.
     *
     * @before _secured, _participant
     */
    public function load()
    {
        $this->disableView();

        $page = (int) RequestMethods::post('page', 0);
        $search = RequestMethods::issetpost('sSearch') ? RequestMethods::post('sSearch') : '';

        if ($search != '') {
            $whereCond = "adv.created LIKE '%%?%%' OR adv.userAlias LIKE '%%?%%' OR adv.title LIKE '%%?%%'";

            $query = \App\Model\AdvertisementModel::getQuery(
                            array('adv.id', 'adv.userId', 'adv.userAlias', 'adv.title',
                                'adv.active', 'adv.state', 'adv.expirationDate', 'adv.created',))
                    ->join('tb_user', 'adv.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
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

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $ads = \App\Model\AdvertisementModel::initialize($query);

            $countQuery = \App\Model\AdvertisementModel::getQuery(array('adv.id'))
                    ->join('tb_user', 'adv.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                    ->wheresql($whereCond, $search, $search, $search);

            $adsCount = \App\Model\AdvertisementModel::initialize($countQuery);
            unset($countQuery);
            $count = count($adsCount);
            unset($adsCount);
        } else {
            $query = \App\Model\AdvertisementModel::getQuery(
                            array('adv.id', 'adv.userId', 'adv.userAlias', 'adv.title',
                                'adv.active', 'adv.state', 'adv.expirationDate', 'adv.created',))
                    ->join('tb_user', 'adv.userId = us.id', 'us', array('us.firstname', 'us.lastname'));

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

            $limit = (int) RequestMethods::post('iDisplayLength');
            $query->limit($limit, $page + 1);
            $ads = \App\Model\AdvertisementModel::initialize($query);
            $count = \App\Model\AdvertisementModel::count();
        }

        $draw = $page + 1 + time();

        $str = '{ "draw": ' . $draw . ', "recordsTotal": ' . $count . ', "recordsFiltered": ' . $count . ', "data": [';

        $returnArr = array();
        if (null !== $ads) {
            foreach ($ads as $ad) {
                $label = '';
                if ($ad->active) {
                    $label .= "<span class='infoLabel infoLabelGreen'>Aktivní</span>";
                } else {
                    $label .= "<span class='infoLabel infoLabelRed'>Neaktivní</span>";
                }

                if ($ad->state == \App\Model\AdvertisementModel::STATE_SOLD) {
                    $label .= "<span class='infoLabel infoLabelOrange'>Prodáno</span>";
                }

                $arr = array();
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
