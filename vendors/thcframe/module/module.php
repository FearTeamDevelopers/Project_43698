<?php

namespace THCFrame\Module;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Events\SubscriberInterface;
use THCFrame\Router\Model\RedirectModel;
use THCFrame\Registry\Registry;

/**
 * Application module class
 */
class Module extends Base
{

    /**
     * @read
     */
    protected $routes = [];

    /**
     * @read
     */
    protected $redirects = [];

    /**
     * @readwrite
     */
    protected $checkForRedirects = false;

    /**
     * @read
     */
    protected $moduleName;

    /**
     * @read
     */
    protected $observerClass = null;

    /**
     * Object constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        Event::fire('framework.module.initialize.before', [$this->moduleName]);

        $this->_addModuleEvents();

        Event::add('framework.router.construct.after', function ($router) {
            $router->addRedirects($this->getRedirects());
            $router->addRoutes($this->getRoutes());
        });

        Event::fire('framework.module.initialize.after', [$this->moduleName]);
    }

    /**
     * Create module-specific events
     */
    private function _addModuleEvents()
    {
        if ($this->getObserverClass() !== null) {
            $obsClass = $this->getObserverClass();
            $moduleObserver = new $obsClass();

            if ($moduleObserver instanceof SubscriberInterface) {
                $events = $moduleObserver->getSubscribedEvents();

                if (count($events)) {
                    foreach ($events as $name => $callback) {
                        if (is_array($callback)) {
                            foreach ($callback as $call) {
                                Event::add($name, [$moduleObserver, $call]);
                            }
                        } else {
                            Event::add($name, [$moduleObserver, $callback]);
                        }
                    }
                }
            }

            unset($moduleObserver);
        }
    }

    /**
     *
     * @param type $method
     * @return \THCFrame\Module\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Get module-specific routes
     *
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Get module-specific redirects
     *
     * @return array
     */
    public function getRedirects()
    {
        if ($this->checkForRedirects) {
            $cache = Registry::get('cache');

            $cachedRedirects = $cache->get('core_redirects_' . strtolower($this->getModuleName()));

            if (null !== $cachedRedirects) {
                $redirects = $cachedRedirects;
            } else {
                $redirects = RedirectModel::all(
                    ['module = ?' => strtolower($this->getModuleName())], ['fromPath', 'toPath']
                );

                if (!empty($redirects)) {
                    $cache->set('core_redirects_' . strtolower($this->getModuleName()), $redirects);
                }
            }

            return $redirects;
        } else {
            return [];
        }
    }

    public function getCheckForRedirects()
    {
        return $this->checkForRedirects;
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getObserverClass()
    {
        return $this->observerClass;
    }

    public function setCheckForRedirects($checkForRedirects)
    {
        $this->checkForRedirects = $checkForRedirects;
        return $this;
    }

    public function setModuleName($moduleName)
    {
        $this->moduleName = $moduleName;
        return $this;
    }

    public function setObserverClass($observerClass)
    {
        $this->observerClass = $observerClass;
        return $this;
    }
}
