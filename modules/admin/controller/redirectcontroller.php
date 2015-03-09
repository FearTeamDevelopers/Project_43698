<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;
use THCFrame\Router\Model\RedirectModel;
use THCFrame\Core\Core;

/**
 * 
 */
class RedirectController extends Controller
{

    /**
     * Get list of all redirects
     * 
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $redirects = RedirectModel::all();
        $view->set('redirects', $redirects);
    }

    /**
     * Create new redirect
     * 
     * @before _secured, _admin
     */
    public function add()
    {
        $view = $this->getActionView();
        $modules = Core::getModuleNames();

        $view->set('submstoken', $this->mutliSubmissionProtectionToken())
                ->set('modules', $modules);

        if (RequestMethods::post('submitAddRedirect')) {
            if ($this->checkCSRFToken() !== true &&
                    $this->checkMutliSubmissionProtectionToken(RequestMethods::post('submstoken')) !== true) {
                self::redirect('/admin/redirect/');
            }

            $redirect = new RedirectModel(array(
                'module' => RequestMethods::post('module'),
                'fromPath' => RequestMethods::post('fromurl'),
                'toPath' => RequestMethods::post('tourl')
            ));

            if ($redirect->validate()) {
                $id = $redirect->save();

                Event::fire('admin.log', array('success', 'Redirect id: ' . $id));
                $view->successMessage('News' . self::SUCCESS_MESSAGE_1);
                self::redirect('/admin/redirect/');
            } else {
                Event::fire('admin.log', array('fail', 'Errors: '.  json_encode($redirect->getErrors())));
                $view->set('errors', $redirect->getErrors())
                        ->set('submstoken', $this->revalidateMutliSubmissionProtectionToken())
                        ->set('redirect', $redirect);
            }
        }
    }

    /**
     * Edit existing redirect
     * 
     * @before _secured, _admin
     * @param int   $id     redirect id
     */
    public function edit($id)
    {
        $view = $this->getActionView();

        $redirect = RedirectModel::first(array('id = ?' => (int) $id));

        if (null === $redirect) {
            $view->warningMessage(self::ERROR_MESSAGE_2);
            $this->_willRenderActionView = false;
            self::redirect('/admin/redirect/');
        }
        
        $modules = Core::getModuleNames();
        $view->set('redirect', $redirect)
                ->set('modules', $modules);

        if (RequestMethods::post('submitEditRedirect')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/redirect/');
            }

            $redirect->module = RequestMethods::post('module');
            $redirect->fromPath = RequestMethods::post('fromurl');
            $redirect->toPath = RequestMethods::post('tourl');

            if ($redirect->validate()) {
                $redirect->save();

                Event::fire('admin.log', array('success', 'Redirect id: ' . $id));
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/redirect/');
            } else {
                Event::fire('admin.log', array('fail', 'Redirect id: ' . $id,
                    'Errors: '.  json_encode($redirect->getErrors())));
                $view->set('errors', $redirect->getErrors());
            }
        }
    }

    /**
     * Delete existing redirect
     * 
     * @before _secured, _admin
     * @param int   $id     redirect id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $redirect = RedirectModel::first(
                        array('id = ?' => (int) $id), array('id')
        );

        if (NULL === $redirect) {
            echo self::ERROR_MESSAGE_2;
        } else {
                if ($redirect->delete()) {
                    Event::fire('admin.log', array('success', 'Redirect id: ' . $id));
                    echo 'success';
                } else {
                    Event::fire('admin.log', array('fail', 'Redirect id: ' . $id));
                    echo self::ERROR_MESSAGE_1;
                }
        }
    }

}
