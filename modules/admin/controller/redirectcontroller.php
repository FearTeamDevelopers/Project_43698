<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Core\Core;
use THCFrame\Events\Events as Event;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\Model\Exception\Validation;
use THCFrame\Request\RequestMethods;
use THCFrame\Router\Model\RedirectModel;
use THCFrame\View\Exception\Data;

/**
 *
 */
class RedirectController extends Controller
{
    /**
     * Get list of all redirects.
     *
     * @before _secured, _superadmin
     * @throws Data
     * @throws Connector
     * @throws Implementation
     */
    public function index(): void
    {
        $view = $this->getActionView();
        $redirects = RedirectModel::all();
        $view->set('redirects', $redirects);
    }

    /**
     * Create new redirect.
     *
     * @before _secured, _superadmin
     * @throws Data
     * @throws Connector
     * @throws Implementation
     * @throws Validation
     */
    public function add(): void
    {
        $view = $this->getActionView();
        $modules = Core::getModuleNames();

        $view->set('modules', $modules);

        if (RequestMethods::post('submitAddRedirect')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true &&
                $this->checkMultiSubmissionProtectionToken() !== true) {
                self::redirect('/admin/redirect/');
            }

            $redirect = new RedirectModel([
                'module' => RequestMethods::post('module'),
                'fromPath' => RequestMethods::post('fromurl'),
                'toPath' => RequestMethods::post('tourl'),
                'created' => date('Y-m-d H:i'),
                'modified' => date('Y-m-d H:i'),
            ]);

            if ($redirect->validate()) {
                $id = $redirect->save();
                $this->getCache()->clearCache();

                Event::fire('admin.log', ['success', 'Redirect id: ' . $id]);
                $view->successMessage($this->lang('CREATE_SUCCESS'));
                self::redirect('/admin/redirect/');
            } else {
                Event::fire('admin.log', ['fail', 'Errors: ' . json_encode($redirect->getErrors())]);
                $view->set('errors', $redirect->getErrors())
                    ->set('submstoken', $this->revalidateMultiSubmissionProtectionToken())
                    ->set('redirect', $redirect);
            }
        }
    }

    /**
     * Edit existing redirect.
     *
     * @before _secured, _superadmin
     *
     * @param int $id redirect id
     * @throws Data
     * @throws Connector
     * @throws Implementation
     */
    public function edit($id): void
    {
        $view = $this->getActionView();

        $redirect = RedirectModel::first(['id = ?' => (int)$id]);

        if (null === $redirect) {
            $view->warningMessage($this->lang('NOT_FOUND'));
            $this->willRenderActionView = false;
            self::redirect('/admin/redirect/');
        }

        $modules = Core::getModuleNames();
        $view->set('redirect', $redirect)
            ->set('modules', $modules);

        if (RequestMethods::post('submitEditRedirect')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/redirect/');
            }

            $redirect->module = RequestMethods::post('module');
            $redirect->fromPath = RequestMethods::post('fromurl');
            $redirect->toPath = RequestMethods::post('tourl');

            if ($redirect->validate()) {
                $redirect->save();
                $this->getCache()->clearCache();

                Event::fire('admin.log', ['success', 'Redirect id: ' . $id]);
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/redirect/');
            } else {
                Event::fire('admin.log', [
                    'fail',
                    'Redirect id: ' . $id,
                    'Errors: ' . json_encode($redirect->getErrors()),
                ]);
                $view->set('errors', $redirect->getErrors());
            }
        }
    }

    /**
     * Delete existing redirect.
     *
     * @before _secured, _superadmin
     *
     * @param int $id redirect id
     * @throws Connector
     * @throws Implementation
     */
    public function delete($id): void
    {
        $this->disableView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            $this->ajaxResponse($this->lang('ACCESS_DENIED'), true, 403);
        }

        $redirect = RedirectModel::first(
            ['id = ?' => (int)$id], ['id']
        );

        if (null === $redirect) {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        } elseif ($redirect->delete()) {
            $this->getCache()->clearCache();
            Event::fire('admin.log', ['success', 'Redirect id: ' . $id]);
            $this->ajaxResponse($this->lang('COMMON_SUCCESS'));
        } else {
            Event::fire('admin.log', ['fail', 'Redirect id: ' . $id]);
            $this->ajaxResponse($this->lang('COMMON_FAIL'), true);
        }
    }
}
