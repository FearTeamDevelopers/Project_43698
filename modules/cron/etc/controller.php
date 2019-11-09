<?php

namespace Cron\Etc;

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
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        // schedule disconnect from database
        Event::add('framework.controller.destruct.after', function () {
            Registry::get('database')->disconnectAll();
        });
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
     * @param $key
     * @param array $args
     * @return mixed|string
     */
    public function lang($key, $args = [])
    {
        return $this->getLang()->_get($key, $args);
    }
}
