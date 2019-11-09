<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use App\Model\PartnerModel;
use Exception;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\FileManager;
use THCFrame\Filesystem\Image;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\Model\Exception\Validation;
use THCFrame\Request\RequestMethods;
use THCFrame\View\Exception\Data;

/**
 *
 */
class PartnerController extends Controller
{
    /**
     * Get list of all partners.
     *
     * @before _secured, _admin
     * @throws Data
     * @throws Connector
     * @throws Implementation
     */
    public function index(): void
    {
        $view = $this->getActionView();

        $partners = PartnerModel::all();

        $view->set('partners', $partners);
    }

    /**
     * Create new partner.
     *
     * @before _secured, _admin
     * @throws Validation
     * @throws Exception
     * @throws Data
     */
    public function add(): void
    {
        $view = $this->getActionView();
        $view->set('partner', null);

        if (RequestMethods::post('submitAddPartner')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/partner/');
            }

            $errors = [];

            $fileManager = new FileManager([
                'thumbWidth' => $this->getConfig()->thumb_width,
                'thumbHeight' => $this->getConfig()->thumb_height,
                'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                'maxImageHeight' => $this->getConfig()->photo_maxheight,
            ]);

            $fileErrors = $fileManager->uploadImage('logo', 'partners', time() . '_', false)->getUploadErrors();
            $files = $fileManager->getUploadedFiles();

            if (!empty($files)) {
                foreach ($files as $i => $file) {
                    if ($file instanceof Image) {
                        $partner = new PartnerModel([
                            'title' => RequestMethods::post('title'),
                            'web' => RequestMethods::post('web'),
                            'logo' => trim($file->getFilename(), '.'),
                            'section' => RequestMethods::post('section'),
                            'rank' => RequestMethods::post('rank', 1),
                            'created' => date('Y-m-d H:i'),
                            'modified' => date('Y-m-d H:i'),
                        ]);

                        if ($partner->validate()) {
                            $id = $partner->save();
                            $this->getCache()->erase('index-partners');

                            Event::fire('admin.log', ['success', 'Partner id: ' . $id]);
                            $view->successMessage($this->lang('CREATE_SUCCESS'));
                            self::redirect('/admin/partner/');
                        } else {
                            Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($partner->getErrors())]);
                            $view->set('errors', $partner->getErrors())
                                ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                                ->set('partner', $partner);
                        }

                        break;
                    }
                }
            } else {
                $errors['logo'] = $fileErrors;
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($errors)]);
                $view->set('errors', $errors)
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken());
            }
        }
    }

    /**
     * Edit existing partner.
     *
     * @before _secured, _admin
     *
     * @param int $id partner id
     * @throws Data
     * @throws Exception
     */
    public function edit($id): void
    {
        $view = $this->getActionView();
        $errors = [];

        $partner = PartnerModel::first(['id = ?' => (int)$id]);

        if (null === $partner) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/partner/');
        }

        $view->set('partner', $partner);

        if (RequestMethods::post('submitEditPartner')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/partner/');
            }

            if ($partner->logo == '') {
                $fileManager = new FileManager([
                    'thumbWidth' => $this->getConfig()->thumb_width,
                    'thumbHeight' => $this->getConfig()->thumb_height,
                    'thumbResizeBy' => $this->getConfig()->thumb_resizeby,
                    'maxImageWidth' => $this->getConfig()->photo_maxwidth,
                    'maxImageHeight' => $this->getConfig()->photo_maxheight,
                ]);

                $fileErrors = $fileManager->uploadImage('logo', 'partners', time() . '_', false)->getUploadErrors();
                $files = $fileManager->getUploadedFiles();

                if (!empty($files)) {
                    foreach ($files as $i => $filemain) {
                        if ($filemain instanceof Image) {
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

                Event::fire('admin.log', ['success', 'Partner id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/partner/');
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Partner id: ' . $id,
                    'Errors: ' . json_encode($errors + $partner->getErrors()),
                ]);
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
     * @throws Connector
     * @throws Implementation
     */
    public function delete($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $partner = PartnerModel::first(
            ['id = ?' => (int)$id], ['id', 'logo']
        );

        if (null === $partner) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } elseif ($partner->delete()) {
            $this->getCache()->erase('index-partners');
            Event::fire('admin.log', ['success', 'Partner id: ' . $id]);
            $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
        } else {
            Event::fire('admin.log', ['fail', 'Partner id: ' . $id]);
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
        }
    }

    /**
     * Delete existing partner logo.
     *
     * @before _secured, _admin
     *
     * @param int $id partner id
     * @throws Connector
     * @throws Implementation
     */
    public function deleteLogo($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $partner = PartnerModel::first(['id = ?' => (int)$id]);

        if (null !== $partner) {
            $path = $partner->getUnlinkLogoPath();
            $partner->logo = '';

            if ($partner->validate()) {
                @unlink($path);
                $partner->save();
                $this->getCache()->erase('index-partners');

                Event::fire('admin.log', ['success', 'Partner id: ' . $id]);
                $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
            } else {
                Event::fire('admin.log', ['fail', 'Partner id: ' . $id]);
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
    public function massAction(): void
    {
        $view = $this->getActionView();
        $errors = [];

        if (RequestMethods::post('performPartnerAction')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/partner/');
            }

            $ids = RequestMethods::post('partnerids');
            $action = RequestMethods::post('action');

            switch ($action) {
                case 'delete':
                    $partners = PartnerModel::all([
                        'id IN ?' => $ids,
                    ]);

                    if (null !== $partners) {
                        foreach ($partners as $partner) {
                            if (unlink($partner->getUnlinkLogoPath())) {
                                if (!$partner->delete()) {
                                    $errors[] = $this->lang('DELETE_FAIL');
                                }
                            } else {
                                $errors[] = $this->lang('DELETE_FAIL') . ' - Logo';
                            }
                        }
                    }

                    if (empty($errors)) {
                        $this->getCache()->erase('index-partners');
                        Event::fire('admin.log', ['delete success', 'Partner ids: ' . implode(',', $ids)]);
                        $view->successMessage($this->lang('DELETE_SUCCESS'));
                    } else {
                        Event::fire('admin.log', ['delete fail', 'Errors:' . json_encode($errors)]);
                        $message = implode('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');

                    break;
                case 'activate':
                    $partners = PartnerModel::all([
                        'id IN ?' => $ids,
                    ]);

                    if (null !== $partners) {
                        foreach ($partners as $partner) {
                            $partner->active = true;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                    . "{$partner->getTitle()} errors: "
                                    . implode(', ', array_shift($partner->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        $this->getCache()->erase('index-partners');
                        Event::fire('admin.log', ['activate success', 'Partner ids: ' . implode(',', $ids)]);
                        $view->successMessage($this->lang('ACTIVATE_SUCCESS'));
                    } else {
                        Event::fire('admin.log', ['activate fail', 'Errors:' . json_encode($errors)]);
                        $message = implode('<br/>', $errors);
                        $view->longFlashMessage($message);
                    }

                    self::redirect('/admin/partner/');

                    break;
                case 'deactivate':
                    $partners = PartnerModel::all([
                        'id IN ?' => $ids,
                    ]);

                    if (null !== $partners) {
                        foreach ($partners as $partner) {
                            $partner->active = false;

                            if ($partner->validate()) {
                                $partner->save();
                            } else {
                                $errors[] = "Partner id {$partner->getId()} - "
                                    . "{$partner->getTitle()} errors: "
                                    . implode(', ', array_shift($partner->getErrors()));
                            }
                        }
                    }

                    if (empty($errors)) {
                        $this->getCache()->erase('index-partners');
                        Event::fire('admin.log', ['deactivate success', 'Partner ids: ' . implode(',', $ids)]);
                        $view->successMessage($this->lang('DEACTIVATE_SUCCESS'));
                    } else {
                        Event::fire('admin.log', ['deactivate fail', 'Errors:' . json_encode($errors)]);
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
