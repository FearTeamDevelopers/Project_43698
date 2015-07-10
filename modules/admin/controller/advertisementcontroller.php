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
     * Check whether unique ad section identifier already exist or not
     * 
     * @param string $key
     * @return boolean
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
     * Get list of all advertisements
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
     * Get list of advertisement sections
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
     * Show detail of existing ad
     * 
     * @before _secured, _participant
     * @param int   $id     ad id
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
     * Delete existing ad
     * 
     * @before _secured, _admin
     * @param int   $id     ad id
     */
    public function delete($id)
    {
        $this->_disableView();

        $ad = \App\Model\AdvertisementModel::first(
                        array('id = ?' => (int) $id), 
                        array('id')
        );

        if (NULL === $ad) {
            echo $this->lang('NOT_FOUND');
        } else {
            if ($ad->delete()) {
                Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $id));
                echo $this->lang('COMMON_FAIL');
            }
        }
    }

    /**
     * Change ad state (active/inactive)
     * 
     * @before _secured, _admin
     * @param int   $id     ad id
     */
    public function changeState($id)
    {
        $this->_disableView();

        $ad = \App\Model\AdvertisementModel::first(array('id = ?' => (int) $id));

        if (NULL === $ad) {
            echo $this->lang('NOT_FOUND');
        } else {
            if ($ad->active) {
                $ad->active = 0;
            } else {
                $ad->active = 1;
            }

            if ($ad->validate()) {
                $ad->save();

                Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $id, 'Errors: '.  json_encode($ad->getErrors())));
                echo $this->lang('COMMON_FAIL');
            }
        }
    }

    /**
     * Delete image from ad
     * 
     * @before _secured, _admin
     * @param int   $imageId     image id
     */
    public function deleteAdImage($imageId)
    {
        $this->_disableView();

        if ($this->_checkCSRFToken()) {
            $photo = \App\Model\AdImageModel::first(
                            array('id = ?' => (int) $imageId), 
                            array('id', 'adId', 'imgMain', 'imgThumb')
            );

            if (null === $photo) {
                echo $this->lang('NOT_FOUND');
            } else {
                $mainPath = $photo->getUnlinkPath();
                $thumbPath = $photo->getUnlinkThumbPath();

                if ($photo->delete()) {
                    @unlink($mainPath);
                    @unlink($thumbPath);

                    Event::fire('admin.log', array('success', 'Ad image id: ' . $imageId
                        . ' from ad: ' . $photo->getAdId()));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Ad image id: ' . $imageId
                        . ' from ad: ' . $photo->getAdId()));
                    echo $this->lang('COMMON_FAIL');
                }
            }
        } else {
            echo $this->lang('COMMON_FAIL');
        }
    }

    /**
     * Create new section for advertisements
     * 
     * @before _secured, _admin
     */
    public function addSection()
    {
        $view = $this->getActionView();

        $view->set('submstoken', $this->_mutliSubmissionProtectionToken())
                ->set('adsection', null);

        if (RequestMethods::post('submitAddAdSection')) {
            if ($this->_checkCSRFToken() !== true &&
                    $this->_checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/advertisement/sections/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkSectionUrlKey($urlKey)) {
                $errors['title'] = array($this->lang('ARTICLE_TITLE_IS_USED'));
            }

            $adsection = new \App\Model\AdSectionModel(array(
                'title' => RequestMethods::post('title'),
                'urlKey' => $urlKey
            ));

            if (empty($errors) && $adsection->validate()) {
                $id = $adsection->save();

                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/advertisement/sections/');
            } else {
                Event::fire('admin.log', array('fail', 'Errors: '.  json_encode($errors+$adsection->getErrors())));
                $view->set('adsection', $adsection)
                        ->set('submstoken', $this->_revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $errors+ $adsection->getErrors());
            }
        }
    }

    /**
     * Edit existing advertisement section
     * 
     * @before _secured, _admin
     * @param int   $id     section id
     */
    public function editSection($id)
    {
        $view = $this->getActionView();

        $adsection = \App\Model\AdSectionModel::first(array('id = ?' => (int) $id));

        if (NULL === $adsection) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            self::redirect('/admin/advertisement/sections/');
        }

        $view->set('adsection', $adsection);

        if (RequestMethods::post('submitEditAdSection')) {
            if ($this->_checkCSRFToken() !== true) {
                self::redirect('/admin/advertisement/sections/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($adsection->getUrlKey() !== $urlKey && !$this->_checkSectionUrlKey($urlKey)) {
                $errors['title'] = array($this->lang('ARTICLE_TITLE_IS_USED'));
            }

            $adsection->title = RequestMethods::post('title');
            $adsection->urlKey = $urlKey;
            $adsection->active = RequestMethods::post('active');

            if (empty($errors) && $adsection->validate()) {
                $adsection->save();

                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/advertisement/sections/');
            } else {
                Event::fire('admin.log', array('fail', 'AdSection id: ' . $id,
                    'Errors: '.  json_encode($errors + $adsection->getErrors())));
                $view->set('errors', $errors + $adsection->getErrors());
            }
        }
    }

    /**
     * Delete existing advertisement section
     * 
     * @before _secured, _admin
     * @param int   $id     section id
     */
    public function deleteSection($id)
    {
        $this->_disableView();

        $adsection = \App\Model\AdSectionModel::first(
                        array('id = ?' => (int) $id), array('id')
        );

        if (NULL === $adsection) {
            echo $this->lang('NOT_FOUND');
        } else {
            if ($adsection->delete()) {
                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'AdSection id: ' . $id,
                    'Errors: '.  json_encode($adsection->getErrors())));
                echo $this->lang('COMMON_FAIL');
            }
        }
    }

    /**
     * Extend ad availability for specific amount of days
     * 
     * @before _secured, _admin
     * @param int   $id     ad id
     */
    public function extendAvailability($id)
    {
        $this->_disableView();

        $ad = \App\Model\AdvertisementModel::first(array('id = ?' => (int) $id, 'hasAvailabilityRequest = ?' => true));

        if (NULL === $ad) {
            echo $this->lang('NOT_FOUND');
        } else {
            $adTtl = $this->getConfig()->bazar_ad_ttl;
            
            $date = new \DateTime();
            $date->add(new \DateInterval('P'.(int)$adTtl.'D'));
            $expirationDate = $date->format('Y-m-d');

            $ad->hasAvailabilityRequest = false;
            $ad->expirationDate = $expirationDate;

            if ($ad->validate()) {
                $ad->save();

                Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Ad id: ' . $id,
                    'Errors: '.  json_encode($ad->getErrors())));
                echo $this->lang('COMMON_FAIL');
            }
        }
    }
}
