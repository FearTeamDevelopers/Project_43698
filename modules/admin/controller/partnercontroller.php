<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Filesystem\FileManager;
use THCFrame\Events\Events as Event;

/**
 *
 */
class PartnerController extends Controller
{
    /**
     * Get list of all partners.
     *
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();

        $partners = \App\Model\PartnerModel::all();

        $view->set('partners', $partners);
    }

    /**
     * Create new partner.
     *
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $view->set('partner', null);

        if (RequestMethods::post('submitAddPartner')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                    $this->checkMutliSubmissionProtectionToken() !== true) {
                self::redirect('/admin/partner/');
            }

            $errors = array();

            $fileManager = new FileManager(array(
                'thumbWidth' => $this->getConfig()->thumb_width,
                'thumbHeight' => $this->getConfig()->thumb_height,
                'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                'maxImageHeight' => $this->getConfig()->photo_maxheight,
            ));

            $fileErrors = $fileManager->uploadImage('logo', 'partners', time().'_', false)->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof \THCFrame\Filesystem\Image) {
                        $partner = new \App\Model\PartnerModel(array(
                            'title' => RequestMethods::post('title'),
                            'web' => RequestMethods::post('web'),
                            'logo' => trim($file->getFilename(), '.'),
                            'section' => RequestMethods::post('section'),
                            'rank' => RequestMethods::post('rank', 1),
                        ));

                        if ($partner->validate()) {
                            $id = $partner->save();
                            $this->getCache()->erase('index-partners');

                            Event::fire('admin.log', array('success', 'Partner id: '.$id));
                            $view->successMessage($this->lang('CREATE_SUCCESS'));
                            self::redirect('/admin/partner/');
                        } else {
                            Event::fire('admin.log', array('fail', 'Errors: '.json_encode($partner->getErrors())));
                            $view->set('errors', $partner->getErrors())
                                    ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                                    ->set('partner', $partner);
                        }

                        break;
                    }
                }
            } else {
                $errors['logo'] = $fileErrors;
                Event::fire('admin.log', array('fail', 'Errors: '.json_encode($errors + $partner->getErrors())));
                $view->set('errors', $errors)
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken());
            }
        }
    }

    /**
     * Edit existing partner.
     *
     * @before _secured, _admin
     *
     * @param int $id partner id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $errors = array();

        $partner = \App\Model\PartnerModel::first(array('id = ?' => (int) $id));

        if (null === $partner) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->_willRenderActionView = false;
            self::redirect('/admin/partner/');
        }

        $view->set('partner', $partner);

        if (RequestMethods::post('submitEditPartner')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/partner/');
            }

            if ($partner->logo == '') {
                $fileManager = new FileManager(array(
                    'thumbWidth' => $this->getConfig()->thumb_width,
                    'thumbHeight' => $this->getConfig()->thumb_height,
                    'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                    'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                    'maxImageHeight' => $this->getConfig()->photo_maxheight,
                ));

                $fileErrors = $fileManager->uploadImage('logo', 'partners', time().'_', false)->getUploadErrors();
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
                $this->getCache()->erase('index-partners');

                Event::fire('admin.log', array('success', 'Partner id: '.$id));
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/partner/');
            } else {
                Event::fire('admin.log', array('fail', 'Partner id: '.$id,
                    'Errors: '.json_encode($errors + $partner->getErrors()), ));
                $view->set('errors', $errors + $partner->getErrors());
            }
        }
    }

    /**
     * Delete existing partner.
     *
     * @before _secured, _admin
     *
     * @param int $id partner id
     */
    public function delete($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $partner = \App\Model\PartnerModel::first(
                        array('id = ?' => (int) $id), array('id', 'logo')
        );

        if (null === $partner) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } else {
            if ($partner->delete()) {
                $this->getCache()->erase('index-partners');
                Event::fire('admin.log', array('success', 'Partner id: '.$id));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', array('fail', 'Partner id: '.$id));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        }
    }

    /**
     * Delete existing partner logo.
     *
     * @before _secured, _admin
     *
     * @param int $id partner id
     */
    public function deleteLogo($id)
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $partner = \App\Model\PartnerModel::first(array('id = ?' => (int) $id));

        if (null !== $partner) {
            $path = $partner->getUnlinkLogoPath();
            $partner->logo = '';

            if ($partner->validate()) {
                @unlink($path);
                $partner->save();
                $this->getCache()->erase('index-partners');

                Event::fire('admin.log', array('success', 'Partner id: '.$id));
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', array('fail', 'Partner id: '.$id));
                $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
            }
        } else {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        }
    }

    /**
     * Execute basic operation over multiple partners.
     *
     * @before _secured, _admin
     */
    public function massAction()
    {
        $view = $this->getActionView();
        $errors = array();

        if (RequestMethods::post('performPartnerAction')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/partner/');
            }

            $ids = RequestMethods::post('partnerids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $partners = \App\Model\PartnerModel::all(array(
                                'id IN ?' => $ids,
                    ));

                    if (null !== $partners) {
                        foreach ($partners as $partner) {
                            if (unlink($partner->getUnlinkLogoPath())) {
                                if (!$partner->delete()) {
                                    $errors[] = $this->lang('DELETE_FAIL');
                                }
                            } else {
                                $errors[] = $this->lang('DELETE_FAIL').' - Logo';
                            }
                        }
                    }

                    if (empty($errors)) {
                        $this->getCache()->erase('index-partners');
                        Event::fire('admin.log', array('delete success', 'Partner ids: '.implode(',', $ids)));
                        $view->successMessage($this->lang('DELETE_SUCCESS'));
                    } else {
                        Event::fire('admin.log', array('delete fail', 'Errors:'.json_encode($errors)));
                        $message = implode('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');

                    break;
                case 'activate':
                    $partners = \App\Model\PartnerModel::all(array(
                                'id IN ?' => $ids,
                    ));

                    if (null !== $partners) {
                        foreach ($partners as $partner) {
                            $partner->active = true;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                        ."{$partner->getTitle()} errors: "
                                        .implode(', ', array_shift($partner->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        $this->getCache()->erase('index-partners');
                        Event::fire('admin.log', array('activate success', 'Partner ids: '.implode(',', $ids)));
                        $view->successMessage($this->lang('ACTIVATE_SUCCESS'));
                    } else {
                        Event::fire('admin.log', array('activate fail', 'Errors:'.json_encode($errors)));
                        $message = implode('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');

                    break;
                case 'deactivate':
                    $partners = \App\Model\PartnerModel::all(array(
                                'id IN ?' => $ids,
                    ));

                    if (null !== $partners) {
                        foreach ($partners as $partner) {
                            $partner->active = false;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                        ."{$partner->getTitle()} errors: "
                                        .implode(', ', array_shift($partner->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        $this->getCache()->erase('index-partners');
                        Event::fire('admin.log', array('deactivate success', 'Partner ids: '.implode(',', $ids)));
                        $view->successMessage($this->lang('DEACTIVATE_SUCCESS'));
                    } else {
                        Event::fire('admin.log', array('deactivate fail', 'Errors:'.json_encode($errors)));
                        $message = implode('<br/>', $errors);
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
