<?php

namespace THCFrame\Router;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Router\Exception;
use THCFrame\Router\Route;
use THCFrame\Request\RequestMethods;

/**
 * Router class
 */
class Router extends Base
{

    /**
     * @readwrite
     */
    protected $_url;

    /**
     * Stores the Route objects
     *
     * @readwrite
     * @var array
     */
    protected $_routes = [];

    /**
     * Stores route redirects.
     * Key represent from path and value is to path
     *
     * @readwrite
     * @var array
     */
    protected $redirects = [];

    /**
     * @readwrite
     * @var Route
     */
    protected $_lastRoute = null;

    /**
     * Application default routes
     *
     * @var array
     */
    private static $_defaultRoutes = [
        [
            'pattern' => '/:module/:controller/:action/:id',
            'module' => ':module',
            'controller' => ':controller',
            'action' => ':action',
            'args' => ':id',
        ],
        [
            'pattern' => '/:module/:controller/:action/',
            'module' => ':module',
            'controller' => ':controller',
            'action' => ':action',
        ],
        [
            'pattern' => '/:controller/:action/:id',
            'module' => 'app',
            'controller' => ':controller',
            'action' => ':action',
            'args' => ':id',
        ],
        [
            'pattern' => '/:module/:controller/',
            'module' => ':module',
            'controller' => ':controller',
            'action' => 'index',
        ],
        [
            'pattern' => '/:controller/:action',
            'module' => 'app',
            'controller' => ':controller',
            'action' => ':action',
        ],
        [
            'pattern' => '/:module/',
            'module' => ':module',
            'controller' => 'index',
            'action' => 'index',
        ],
        [
            'pattern' => '/:controller',
            'module' => 'app',
            'controller' => ':controller',
            'action' => 'index',
        ],
        [
            'pattern' => '/',
            'module' => 'app',
            'controller' => 'index',
            'action' => 'index',
        ]
    ];

    /**
     * Object constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        Event::fire('framework.router.construct.before', []);

        $this->_createRoutes(self::$_defaultRoutes);

        Event::fire('framework.router.construct.after', [$this]);

        $this->_findRoute($this->_url);
    }

    /**
     *
     * @param string $method
     * @return \THCFrame\Router\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Create routes
     */
    private function _createRoutes(array $routes = [])
    {
        if (!empty($routes)) {
            foreach ($routes as $route) {
                $newRoute = new Route\Dynamic(['pattern' => $route['pattern']]);

                if (preg_match('/^:/', $route['module'])) {
                    $newRoute->addDynamicElement(':module', ':module');
                } else {
                    $newRoute->setModule($route['module']);
                }

                if (preg_match('/^:/', $route['controller'])) {
                    $newRoute->addDynamicElement(':controller', ':controller');
                } else {
                    $newRoute->setController($route['controller']);
                }

                if (preg_match('/^:/', $route['action'])) {
                    $newRoute->addDynamicElement(':action', ':action');
                } else {
                    $newRoute->setAction($route['action']);
                }

                if (isset($route['method']) && !empty($route['method'])) {
                    if (RequestMethods::isAllowedMethod($route['method'])) {
                        $newRoute->setMethod($route['method']);
                    } else {
                        throw new Exception('Unknown method. Use GET, POST, PUT or DELETE only');
                    }
                }

                if (isset($route['args']) && is_array($route['args'])) {
                    foreach ($route['args'] as $arg) {
                        if (preg_match('/^:/', $arg)) {
                            $newRoute->addDynamicElement($arg, $arg);
                        }
                    }
                } elseif (isset($route['args']) && !is_array($route['args'])) {
                    if (preg_match('/^:/', $route['args'])) {
                        $newRoute->addDynamicElement($route['args'], $route['args']);
                    }
                }

                $this->addRoute($newRoute);
            }
        }
    }

    /**
     * Finds a maching route in the routes array using specified $path
     *
     * @param string $path
     */
    private function _findRoute($path)
    {
        Event::fire('framework.router.findroute.checkredirect.before', [$path]);

        if (count($this->redirects)) {
            if (isset($this->redirects[$path])) {
                $path = $this->redirects[$path];
            }
        }

        Event::fire('framework.router.findroute.checkredirect.after', [$path]);
        Event::fire('framework.router.findroute.before', [$path]);

        foreach ($this->_routes as $route) {
            if ($route->matchMap($path) === true) {
                $this->_lastRoute = $route;
                break;
            }
        }

        if ($this->_lastRoute === null) {
            throw new Exception\Module('Not found');
        }

        Event::fire('framework.router.findroute.after', [
            $path,
            $this->_lastRoute->getModule(),
            $this->_lastRoute->getController(),
            $this->_lastRoute->getAction()]
        );
    }

    /**
     * Add route to route collection
     *
     * @param \THCFrame\Router\Route $route
     * @return \THCFrame\Router\Router
     */
    public function addRoute(\THCFrame\Router\Route $route)
    {
        array_unshift($this->_routes, $route);
        //$this->_routes[] = $route;
        return $this;
    }

    /**
     * Remove route from route collection
     *
     * @param \THCFrame\Router\Route $route
     * @return \THCFrame\Router\Router
     */
    public function removeRoute(\THCFrame\Router\Route $route)
    {
        foreach ($this->_routes as $i => $stored) {
            if ($stored == $route) {
                unset($this->_routes[$i]);
            }
        }
        return $this;
    }

    /**
     * Return list of all routes in routes array
     *
     * @return array $list
     */
    public function getRoutes()
    {
        $list = [];

        foreach ($this->_routes as $route) {
            $list[$route->getPattern()] = get_class($route);
        }

        return $list;
    }

    /**
     *
     * @param array $redirects
     */
    public function addRedirects(array $redirects)
    {
        if (!empty($redirects)) {
            foreach ($redirects as $redirect) {
                $this->redirects[$redirect->getFromPath()] = $redirect->getToPath();
            }
        }
    }

    /**
     * Return all stored redirects
     *
     * @return type
     */
    public function getRedirects()
    {
        return $this->redirects;
    }

    /**
     * Set redirects
     *
     * @param array $redirects
     * @return $this
     */
    public function setRedirects($redirects = [])
    {
        $this->redirects = $redirects;
        return $this;
    }

    /**
     * Public method for _createRoutes method
     *
     * @param array $routes
     */
    public function addRoutes(array $routes)
    {
        $this->_createRoutes($routes);
    }

}
