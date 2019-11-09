<?php
namespace Queue\Etc;

use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;

/**
 * Module specific controller class extending framework controller class.
 */
class Controller extends BaseController
{

    /**
     * Disable view, used for ajax calls.
     */
    protected function disableView()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
    }

    /**
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        // schedule disconnect from database
        Event::add('framework.controller.destruct.after', function () {
            Registry::get('database')->disconnectAll();
        });
    }

    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');

        //This line should be present only for DEV env
        //$this->getSecurity()->forceLogin(1);

        $user = $this->getSecurity()->getUser();

        if (!$user) {
            $this->willRenderActionView = false;
            $this->willRenderLayoutView = false;
            self::redirect('/admin/login');
        }

        //5h inactivity till logout
        if (time() - $session->get('lastActive') < 18000) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage($this->lang('LOGIN_TIMEOUT'));
            $this->getSecurity()->logout();
            self::redirect('/admin/login');
        }
    }

    /**
     * @protected
     */
    public function _cron()
    {
        if (!preg_match('#^Links.*#i', RequestMethods::server('HTTP_USER_AGENT')) &&
            '95.168.206.203' != RequestMethods::server('REMOTE_ADDR')) {
            throw new \THCFrame\Security\Exception\Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isCron()
    {
        return preg_match('#^Links.*#i', RequestMethods::server('HTTP_USER_AGENT')) &&
            '95.168.206.203' == RequestMethods::server('REMOTE_ADDR');
    }

    /**
     * @protected
     */
    public function _admin()
    {
        if ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_admin') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isAdmin()
    {
        return $this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_admin') === true;
    }

    /**
     * @protected
     */
    public function _superadmin()
    {
        if ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_superadmin') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isSuperAdmin()
    {
        return $this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_superadmin') === true;
    }

    /**
     *
     */
    public function render()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        if ($view) {
            $view->set('authUser', $this->getSecurity()->getUser())
                ->set('env', ENV)
                ->set('isAdmin', $this->isAdmin())
                ->set('isSuperAdmin', $this->isSuperAdmin())
                ->set('submstoken', $this->mutliSubmissionProtectionToken())
                ->set('token', $this->getSecurity()->getCsrf()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->getSecurity()->getUser())
                ->set('env', ENV)
                ->set('isAdmin', $this->isAdmin())
                ->set('isSuperAdmin', $this->isSuperAdmin())
                ->set('submstoken', $this->mutliSubmissionProtectionToken())
                ->set('token', $this->getSecurity()->getCsrf()->getToken());
        }

        parent::render();
    }

    /**
     * Load user from security context.
     */
    public function getUser()
    {
        return $this->getSecurity()->getUser();
    }

    /**
     * @param type $key
     * @param array $args
     *
     * @return type
     */
    public function lang($key, $args = [])
    {
        return $this->getLang()->_get($key, $args);
    }
}
