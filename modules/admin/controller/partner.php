<?php

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Filesystem\FileManager;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 * 
 */
class Admin_Controller_Partner extends Controller
{

    /**
     * Get list of all partners
     * 
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        $partners = App_Model_Partner::all();

        $view->set('partners', $partners);
    }

    /**
     * Create new partner
     * 
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $view->set('submstoken', $this->mutliSubmissionProtectionToken());

        if (RequestMethods::post('submitAddPartner')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/partner/');
            }

            $errors = array();

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->getConfig()->thumb_width,
                'thumbHeight' => $this->getConfig()->thumb_height,
                'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                'maxImageHeight' => $this->getConfig()->photo_maxheight
            ));

            $fileErrors = $fileManager->uploadImage('logo', 'partners', time() . '_', false)->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $partner = new App_Model_Partner(array(
                            'title' => RequestMethods::post('title'),
                            'web' => RequestMethods::post('web'),
                            'logo' => trim($file->getFilename(), '.'),
                            'section' => RequestMethods::post('section'),
                            'rank' => RequestMethods::post('rank', 1)
                        ));

                        if ($partner->validate()) {
                            $id = $partner->save();

                            Event::fire('admin.log', array('success', 'Partner id: ' . $id));
                            $view->successMessage('Partner' . self::SUCCESS_MESSAGE_1);
                            self::redirect('/admin/partner/');
                        } else {
                            Event::fire('admin.log', array('fail'));
                            $view->set('errors', $partner->getErrors())
                                    ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                                    ->set('partner', $partner);
                        }

                        break;
                    }
                }
            } else {
                $errors['logo'] = $fileErrors;
                Event::fire('admin.log', array('fail'));
                $view->set('errors', $errors)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

    /**
     * Edit existing partner
     * 
     * @before _secured, _admin
     * @param int   $id     partner id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $errors = array();

        $partner = App_Model_Partner::first(array('id = ?' => (int) $id));

        if (NULL === $partner) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/partner/');
        }

        $view->set('partner', $partner);

        if (RequestMethods::post('submitEditPartner')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/partner/');
            }

            if ($partner->logo == '') {
                $fileManager = new FileManager(array(
                    'thumbWidth' => $this->getConfig()->thumb_width,
                    'thumbHeight' => $this->getConfig()->thumb_height,
                    'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                    'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                    'maxImageHeight' => $this->getConfig()->photo_maxheight
                ));

                $fileErrors = $fileManager->uploadImage('logo', 'partners', time() . '_', false)->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($files)) {
                    foreach ($files as $i => $filemain) {
                        if ($filemain instanceof \THCFrame\Filesystem\Image) {
                            $file = $filemain;
                            break;
                        }
                    }

                    $logo = trim($file->getFilename(), '.');
                } else {
                    $errors['logo'] = $fileErrors;
                }
            } else {
                $logo = $partner->logo;
            }

            $partner->title = RequestMethods::post('title');
            $partner->web = RequestMethods::post('web');
            $partner->section = RequestMethods::post('section');
            $partner->logo = $logo;
            $partner->rank = RequestMethods::post('rank', 1);
            $partner->active = RequestMethods::post('active');

            if (empty($errors) && $partner->validate()) {
                $partner->save();

                Event::fire('admin.log', array('success', 'Partner id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/partner/');
            } else {
                Event::fire('admin.log', array('fail', 'Partner id: ' . $id));
                $view->set('errors', $errors + $partner->getErrors());
            }
        }
    }

    /**
     * Delete existing partner
     * 
     * @before _secured, _admin
     * @param int   $id     partner id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $partner = App_Model_Partner::first(
                        array('id = ?' => (int) $id), array('id', 'logo')
        );

        if (NULL === $partner) {
            echo self::ERROR_MESSAGE_2;
        } else {
            $path = $partner->getUnlinkLogoPath();

            if ($partner->delete()) {
                @unlink($path);
                Event::fire('admin.log', array('success', 'Partner id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Partner id: ' . $id));
                echo self::ERROR_MESSAGE_1;
            }
        }
    }

    /**
     * Delete existing partner logo
     * 
     * @before _secured, _admin
     * @param int   $id     partner id
     */
    public function deleteLogo($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $partner = App_Model_Partner::first(array('id = ?' => (int) $id));

        if (NULL !== $partner) {
            $path = $partner->getUnlinkLogoPath();
            $partner->logo = '';

            if ($partner->validate()) {
                @unlink($path);
                $partner->save();

                Event::fire('admin.log', array('success', 'Partner id: ' . $id));
                echo 'success';
            } else {
                Event::fire('admin.log', array('fail', 'Partner id: ' . $id));
                echo self::ERROR_MESSAGE_5;
            }
        } else {
            Event::fire('admin.log', array('fail', 'Partner id: ' . $id));
            echo self::ERROR_MESSAGE_2;
        }
    }

    /**
     * Execute basic operation over multiple partners
     * 
     * @before _secured, _admin
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performPartnerAction')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/partner/');
            }

            $ids = RequestMethods::post('partnerids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $partners = App_Model_Partner::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $partners) {
                        foreach ($partners as $partner) {
                            if (unlink($partner->getUnlinkLogoPath())) {
                                if (!$partner->delete()) {
                                    $errors[] = 'An error occured while deleting ' . $partner->getTitle();
                                }
                            } else {
                                $errors[] = 'An error occured while deleting logo of ' . $partner->getTitle();
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('delete success', 'Partner ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_6);
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');

                    break;
                case 'activate':
                    $partners = App_Model_Partner::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $partners) {
                        foreach ($partners as $partner) {
                            $partner->active = true;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                        . "{$partner->getTitle()} errors: "
                                        . join(', ', array_shift($partner->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('activate success', 'Partner ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_4);
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');

                    break;
                case 'deactivate':
                    $partners = App_Model_Partner::all(array(
                                'id IN ?' => $ids
                    ));

                    if (NULL !== $partners) {
                        foreach ($partners as $partner) {
                            $partner->active = false;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                        . "{$partner->getTitle()} errors: "
                                        . join(', ', array_shift($partner->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        Event::fire('admin.log', array('deactivate success', 'Partner ids: ' . join(',', $ids)));
                        $view->successMessage(self::SUCCESS_MESSAGE_5);
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Error count:' . count($errors)));
                        $message = join('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');
                    break;
                default:
                    self::redirect('/admin/partner/');
                    break;
            }
        }
    }

}
