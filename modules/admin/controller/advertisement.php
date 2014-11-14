<?php

use Admin\Etc\Controller;

/**
 * 
 */
class Admin_Controller_Advertisement extends Controller
{

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

        if ($this->checkCSRFToken()) {
            $ad = App_Model_Advertisement::first(
                            array('id = ?' => (int) $id), array('id', 'userId')
            );

            if (NULL === $ad) {
                echo self::ERROR_MESSAGE_2;
            } else {
                if ($ad->delete()) {
                    Event::fire('admin.log', array('success', 'Ad id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Ad id: ' . $id));
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
    public function changeState($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        if ($this->checkCSRFToken()) {
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
        } else {
            echo self::ERROR_MESSAGE_1;
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
                            array('id = ?' => (int) $imageId), 
                            array('id', 'adId', 'imgMain', 'imgThumb')
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

}
