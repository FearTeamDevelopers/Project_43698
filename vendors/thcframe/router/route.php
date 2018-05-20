<?php

namespace THCFrame\Router;

use THCFrame\Core\Base;
use THCFrame\Router\Exception;

/**
 * Router\Route class inherits from the Base class, so we can define all manner of simulated
 * getters/setters.
 * All of the protected properties relate to the variables provided when a
 * new Router\Route (or subclass) instance are created, and contain information about the URL requested.
 */
abstract class Route extends Base
{

    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_DELETE = 'DELETE';

    /**
     * The Route path consisting of route elements
     *
     * @var string
     * @readwrite
     */
    protected $pattern;

    /**
     * The name of the module that this route maps to
     *
     * @var type
     * @readwrite
     */
    protected $module;

    /**
     * The name of the class that this route maps to
     *
     * @var string
     * @readwrite
     */
    protected $controller;

    /**
     * The name of the class method that this route maps to
     *
     * @var string
     * @readwrite
     */
    protected $action;

    /**
     * HTTP request method
     * GET, POST, PUT, DELETE
     *
     * @var string
     * @readwrite
     */
    protected $method = null;

    /**
     *
     * @param string $method
     * @return \THCFrame\Router\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    public function getPattern()
    {
        return $this->pattern;
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function setModule($module)
    {
        $this->module = $module;
        return $this;
    }

    public function setController($controller)
    {
        $this->controller = $controller;
        return $this;
    }

    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    abstract public function matchMap($pathToMatch);
}
