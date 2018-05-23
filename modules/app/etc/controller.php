<?php

namespace App\Etc;

use THCFrame\Events\Events as Event;
use THCFrame\Controller\Controller as BaseController;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;

/**
 * Module specific controller class extending framework controller class.
 */
class Controller extends BaseController
{

    /**
     * Controller constructor.
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        // schedule disconnect from database
        Event::add('framework.controller.destruct.after', function ($name) {
            Registry::get('database')->disconnectAll();
        });

        $metaData = $this->getCache()->get('global_meta_data');

        if (null === $metaData) {
            $metaData = [
                'metadescription' => $this->getConfig()->meta_description,
                'metarobots' => $this->getConfig()->meta_robots,
                'metatitle' => $this->getConfig()->meta_title,
                'metaogurl' => $this->getConfig()->meta_og_url,
                'metaogtype' => $this->getConfig()->meta_og_type,
                'metaogimage' => $this->getConfig()->meta_og_image,
                'metaogsitename' => $this->getConfig()->meta_og_site_name,
                'showfeedback' => $this->getConfig()->show_feedback,
            ];

            $this->getCache()->set('global_meta_data', $metaData);
        }

        $this->getLayoutView()
                ->set('metatitle', $metaData['metatitle'])
                ->set('metarobots', $metaData['metarobots'])
                ->set('metadescription', $metaData['metadescription'])
                ->set('metaogurl', $metaData['metaogurl'])
                ->set('metaogtype', $metaData['metaogtype'])
                ->set('metaogimage', $metaData['metaogimage'])
                ->set('metaogsitename', $metaData['metaogsitename'])
                ->set('showfeedback', $metaData['showfeedback']);
    }

    /**
     * @param int $pageCount
     * @param int $page
     * @param string $path
     */
    protected function pagerMetaLinks($pageCount, $page, $path)
    {
        if ($pageCount > 1) {
            $prevPage = $page - 1;
            $nextPage = $page + 1;

            if ($nextPage > $pageCount) {
                $nextPage = 0;
            }

            $this->getLayoutView()
                    ->set('pagedprev', $prevPage)
                    ->set('pagedprevlink', $path . $prevPage)
                    ->set('pagednext', $nextPage)
                    ->set('pagednextlink', $path . $nextPage);
        }
    }

    /**
     * Disable view, used for ajax calls.
     */
    protected function disableView()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
    }

    /**
     * @protected
     */
    public function _secured()
    {
        $session = Registry::get('session');
        $user = $this->getSecurity()->getUser();

        if (!$user) {
            $this->disableView();
            self::redirect('/prihlasit');
        }

        //1h inactivity till logout
        if (time() - $session->get('lastActive') < 3600) {
            $session->set('lastActive', time());
        } else {
            $view = $this->getActionView();

            $view->infoMessage($this->lang('LOGIN_TIMEOUT'));
            $this->getSecurity()->logout();
            self::redirect('/');
        }
    }

    /**
     * @protected
     */
    public function _member()
    {
        if ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_member') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isMember()
    {
        return ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_member') === true);
    }

    /**
     * @protected
     */
    public function _participant()
    {
        if ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_participant') !== true) {
            throw new \THCFrame\Security\Exception\Unauthorized($this->lang('ACCESS_DENIED'));
        }
    }

    /**
     * @return bool
     */
    protected function isParticipant()
    {
        return ($this->getSecurity()->getUser() && $this->getSecurity()->isGranted('role_participant') === true);
    }

    /**
     * Load user from security context.
     */
    public function getUser()
    {
        return $this->getSecurity()->getUser();
    }

    /**
     * @param string $key
     * @param array $args
     * @return mixed|string
     */
    public function lang($key, $args = [])
    {
        return $this->getLang()->_get($key, $args);
    }

    /**
     * @return bool
     */
    protected function checkCookieConsent()
    {
        return RequestMethods::issetcookie('cookies-consent-mandatory');
    }

    /**
     * @throws \THCFrame\View\Exception\Renderer
     */
    public function render()
    {
        $view = $this->getActionView();
        $layoutView = $this->getLayoutView();

        if ($view) {
            $view->set('authUser', $this->getSecurity()->getUser())
                    ->set('deviceType', $this->getDeviceType())
                    ->set('cookieConsent', $this->checkCookieConsent())
                    ->set('env', ENV)
                    ->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('submstoken', $this->mutliSubmissionProtectionToken())
                    ->set('token', $this->getSecurity()->getCsrf()->getToken());
        }

        if ($layoutView) {
            $layoutView->set('authUser', $this->getSecurity()->getUser())
                    ->set('deviceType', $this->getDeviceType())
                    ->set('cookieConsent', $this->checkCookieConsent())
                    ->set('env', ENV)
                    ->set('isMember', $this->isMember())
                    ->set('isParticipant', $this->isParticipant())
                    ->set('submstoken', $this->mutliSubmissionProtectionToken())
                    ->set('token', $this->getSecurity()->getCsrf()->getToken());
        }

        parent::render();
    }

}
