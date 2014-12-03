<?php

use Admin\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;

/**
 * 
 */
class Admin_Controller_Advertisement extends Controller
{

    /**
     * 
     * @param type $key
     * @return boolean
     */
    private function _checkSectionUrlKey($key)
    {
        $status = App_Model_AdSection::first(array('urlKey = ?' => $key));

        if ($status === null) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @before _secured, _participant
     */
    public function index()
    {
        $view = $this->getActionView();
        $ads = App_Model_Advertisement::fetchAll();
        $view->set('ads', $ads);
    }

    /**
     * @before _secured, _participant
     */
    public function sections()
    {
        $view = $this->getActionView();
        $adsections = App_Model_AdSection::fetchAll();
        $view->set('adsections', $adsections);
    }

    /**
     * @before _secured, _participant
     */
    public function detail($id)
    {
        $view = $this->getActionView();
        $ad = App_Model_Advertisement::fetchById($id);

        if ($ad === null) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/advertisement/');
        }

        $view->set('ad', $ad);
    }

    /**
     * @before _secured, _admin
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $ad = App_Model_Advertisement::first(
                        array('id = ?' => (int) $id), array('id', 'userId')
        );

        if (NULL === $ad) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($this->_security->isGranted('role_admin') === true ||
                    $ad->getUserId() == $this->getUser()->getId()) {
                if ($ad->delete()) {
                    Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Ad id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
            } else {
                echo self::ERROR_MESSAGE_4;
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function changeState($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $ad = App_Model_Advertisement::first(array('id = ?' => (int) $id));

        if (NULL === $ad) {
            echo self::ERROR_MESSAGE_2;
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
                Event::fire('admin.log', array('fail', 'Ad id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function deleteAdImage($imageId)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
            $photo = App_Model_AdImage::first(
                            array('id = ?' => (int) $imageId), array('id', 'adId', 'imgMain', 'imgThumb')
            );

            if (null === $photo) {
                echo self::ERROR_MESSAGE_2;
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
                    echo self::ERROR_MESSAGE_1;
                }
            }
        } else {
            echo self::ERROR_MESSAGE_1;
        }
    }

    /**
     * @before _secured, _admin
     */
    public function addSection()
    {
        $view = $this->getActionView();

        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddAdSection')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/advertisement/sections/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if (!$this->_checkSectionUrlKey($urlKey)) {
                $errors['title'] = array('Ad section with this title already exists');
            }

            $adsection = new App_Model_AdSection(array(
                'title' => RequestMethods::post('title'),
                'urlKey' => $urlKey
            ));

            if (empty($errors) && $adsection->validate()) {
                $id = $adsection->save();

                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                $view->successMessage('Gallery' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/advertisement/sections/');
            } else {
                Event::fire('admin.log', array('fail'));
                $view->set('adsection', $adsection)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('errors', $adsection->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function editSection($id)
    {
        $view = $this->getActionView();

        $adsection = App_Model_AdSection::first(array('id = ?' => (int) $id));

        if (NULL === $adsection) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            self::redirect('/admin/advertisement/sections/');
        }

        $view->set('adsection', $adsection);

        if (RequestMethods::post('submitEditAdSection')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/advertisement/sections/');
            }

            $errors = array();
            $urlKey = $this->_createUrlKey(RequestMethods::post('title'));

            if ($adsection->getUrlKey() !== $urlKey && !$this->_checkSectionUrlKey($urlKey)) {
                $errors['title'] = array('Ad section with this title already exists');
            }

            $adsection->title = RequestMethods::post('title');
            $adsection->urlKey = $urlKey;
            $adsection->active = RequestMethods::post('active');

            if (empty($errors) && $adsection->validate()) {
                $adsection->save();

                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/advertisement/sections/');
            } else {
                Event::fire('admin.log', array('fail', 'AdSection id: ' . $id));
                $view->set('errors', $adsection->getErrors());
            }
        }
    }

    /**
     * @before _secured, _admin
     */
    public function deleteSection($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $adsection = App_Model_AdSection::first(
                        array('id = ?' => (int) $id), array('id')
        );

        if (NULL === $adsection) {
            echo self::ERROR_MESSAGE_2;
        } else {
            if ($adsection->delete()) {
                Event::fire('admin.log', array('success', 'AdSection id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'AdSection id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

}
