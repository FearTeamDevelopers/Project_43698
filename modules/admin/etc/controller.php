<?php

namespace Admin\Etc;

use Exception;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Request\RequestMethods;
use THCFrame\Security\Exception\Unauthorized;
use THCFrame\Security\Model\BasicUserModel;

/**
 * Module specific controller class extending framework controller class.
 */
class Controller extends BaseController
{

    /**
     * Disable view, used for ajax calls.
     */
    protected function disableView(): void
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
    }

    /**
     * @param array $options
     * @throws Exception
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        // schedule disconnect from database
        Event::add('framework.controller.destruct.after', static function () {
            Registry::get('database')->disconnectAll();
        });
    }

    /**
     * @protected
     */
    public function _secured(): void
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
     * @throws Unauthorized
     */
    public function _cron(): void
    {
        if (!preg_match('#^Links.*#i', RequestMethods::server('HTTP_USER_AGENT')) &&
                '95.168.206.203' != RequestMethods::server('REMOTE_ADDR')) {
            throw new Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isCron(): ?bool
    {
        return preg_match('#^Links.*#i', RequestMethods::server('HTTP_USER_AGENT')) &&
            '95.168.206.203' == RequestMethods::server('REMOTE_ADDR');
    }

    /**
     * @protected
     * @throws Unauthorized
     */
    public function _member(): void
    {
        if ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_member') !== true) {
            throw new Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isMember(): ?bool
    {
        return $this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_member') === true;
    }

    /**
     * @protected
     * @throws Unauthorized
     */
    public function _participant(): void
    {
        if ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_participant') !== true) {
            throw new Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isParticipant(): ?bool
    {
        return $this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_participant') === true;
    }

    /**
     * @protected
     * @throws Unauthorized
     */
    public function _admin(): void
    {
        if ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_admin') !== true) {
            throw new Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isAdmin(): ?bool
    {
        return $this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_admin') === true;
    }

    /**
     * @protected
     * @throws Unauthorized
     */
    public function _superadmin(): void
    {
        if ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_superadmin') !== true) {
            throw new Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isSuperAdmin(): ?bool
    {
        return $this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_superadmin') === true;
    }

    /**
     * @throws \THCFrame\View\Exception\Data
     * @throws \THCFrame\View\Exception\Renderer
     */
    public function render()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        if ($view) {
            $view->set('authUser', $this->getSecurity()->getUser())
                    ->set('env', ENV)
                    ->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('submstoken', $this->mutliSubmissionProtectionToken())
                    ->set('token', $this->getSecurity()->getCsrf()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->getSecurity()->getUser())
                    ->set('env', ENV)
                    ->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('isAdmin', $this->isAdmin())
                    ->set('isSuperAdmin', $this->isSuperAdmin())
                    ->set('submstoken', $this->mutliSubmissionProtectionToken())
                    ->set('token', $this->getSecurity()->getCsrf()->getToken());
        }

        parent::render();
    }

    /**
     * Load user from security context.
     * @return BasicUserModel|null
     */
    public function getUser(): ?BasicUserModel
    {
        return $this->getSecurity()->getUser();
    }

    /**
     * @param $key
     * @param array $args
     * @return mixed|string
     */
    public function lang($key, $args = [])
    {
        return $this->getLang()->_get($key, $args);
    }
}
