<?php

namespace THCFrame\Router;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Core\Inspector;
use THCFrame\Router\Exception;

/**
 * Dispatcher class will use the requested URL, as well as the controller/action metadata,
 * to determine the correct controller/action to execute.
 * It needs to handle multiple defined routes and inferred routes if no defined routes
 * are matched.
 */
final class Dispatcher extends Base
{

    /**
     * Module active for last request
     *
     * @var type
     * @read
     */
    protected $_activeModule;

    /**
     * The suffix used to append to the class name
     *
     * @var string
     * @read
     */
    protected $_suffix;

    /**
     * The path to look for classes (or controllers)
     *
     * @var string
     * @read
     */
    protected $_controllerPath;

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
     * Sets a suffix to append to the class name being dispatched
     *
     * @param string $suffix
     * @return \THCFrame\Router\Dispatcher
     */
    protected function _setSuffix($suffix)
    {
        $this->_suffix = '.' . $suffix;

        return $this;
    }

    /**
     * Get class name suffix
     *
     * @return string
     */
    protected function _getSuffix()
    {
        return $this->_suffix;
    }

    /**
     * Set the path where dispatch class (controllers) reside
     *
     * @param string $path
     * @return \THCFrame\Router\Dispatcher
     */
    protected function _setControllerPath($path)
    {
        $this->_controllerPath = preg_replace('/\/$/', '', $path) . '/';

        return $this;
    }

    /**
     * Initialisation method
     */
    public function initialize()
    {
        Event::fire('framework.dispatcher.initialize.before', []);

        $configuration = Registry::get('configuration');

        if (!empty($configuration->dispatcher)) {
            $this->_setSuffix($configuration->dispatcher->suffix);
        } else {
            throw new Exception\Argument('Error in configuration file');
        }

        Event::fire('framework.dispatcher.initialize.after', []);

        return $this;
    }

    /**
     * Attempts to dispatch the supplied Route object
     *
     * @param \THCFrame\Router\Route $route
     * @throws Exception\Module
     * @throws Exception\Controller
     * @throws Exception\Action
     */
    public function dispatch(\THCFrame\Router\Route $route)
    {
        $module = trim($route->getModule());
        $class = trim($route->getController());
        $action = trim($route->getAction());
        $method = $route->getMethod() === null ? null : trim($route->getMethod());
        $parameters = $route->getMapArguments();
        $status = null;

        if ('' === $module) {
            throw new Exception\Module('Module Name not specified');
        } elseif ('' === $class) {
            throw new Exception\Controller('Class Name not specified');
        } elseif ('' === $action) {
            throw new Exception\Action('Method Name not specified');
        }

        if ($method !== null) {
            $action .= ucfirst($method);
        }

        if (in_array(ucfirst($module), \THCFrame\Core\Core::getModuleNames())) {
            $status = isset(Registry::get('configuration')->{$module . 'status'}) ? Registry::get('configuration')->{$module . 'status'} : null;
        }

        if ($status !== null && $status != 1) {
            throw new Exception\Offline('Application is offline');
        }

        $module = str_replace('\\', '', $module);
        preg_match('/^[a-zA-Z0-9_]+$/', $module, $matches);

        if (count($matches) !== 1) {
            throw new Exception\Module(sprintf('Disallowed characters in module name %s', $module));
        }

        $class = str_replace('\\', '', $class);
        preg_match('/^[a-zA-Z0-9_]+$/', $class, $matches);

        if (count($matches) !== 1) {
            throw new Exception\Controller(sprintf('Disallowed characters in class name %s', $class));
        }

        $fileName = strtolower("./modules/{$module}/controller/{$class}controller.php");
        $class = "\\" . ucfirst($module) . "\\Controller\\" . ucfirst($class) . 'Controller';

        if (!file_exists($fileName)) {
            throw new Exception\Controller(sprintf('Class file %s not found', $fileName));
        } else {
            require_once($fileName);
        }

        $this->_activeModule = $module;

        Event::fire('framework.dispatcher.controller.before', [$class, $parameters]);

        try {
            $instance = new $class([
                'parameters' => $parameters
            ]);
            Registry::set('controller', $instance);
        } catch (\Exception $e) {
            throw new Exception\Controller(sprintf('Controller %s error: %s', $class, $e->getMessage()));
        }

        Event::fire('framework.dispatcher.controller.after', [$class, $parameters]);

        if (!method_exists($instance, $action)) {
            $instance->willRenderLayoutView = false;
            $instance->willRenderActionView = false;

            throw new Exception\Action(sprintf('Action %s not found', $action));
        }

        $inspector = new Inspector($instance);
        $methodMeta = $inspector->getMethodMeta($action);

        if (!empty($methodMeta['@protected']) || !empty($methodMeta['@private'])) {
            throw new Exception\Action(sprintf('Action %s not found', $action));
        }

        $hooks = function($meta, $type) use ($inspector, $instance) {
            if (isset($meta[$type])) {
                $run = [];

                foreach ($meta[$type] as $method) {
                    $hookMeta = $inspector->getMethodMeta($method);

                    if (in_array($method, $run) && !empty($hookMeta['@once'])) {
                        continue;
                    }

                    $instance->$method();
                    $run[] = $method;
                }
            }
        };

        Event::fire('framework.dispatcher.beforehooks.before', [$action, $parameters]);

        $hooks($methodMeta, '@before');

        Event::fire('framework.dispatcher.beforehooks.after', [$action, $parameters]);
        Event::fire('framework.dispatcher.action.before', [$action, $parameters]);

        call_user_func_array(
                [$instance, $action], is_array($parameters) ? $parameters : []);

        Event::fire('framework.dispatcher.action.after', [$action, $parameters]);
        Event::fire('framework.dispatcher.afterhooks.before', [$action, $parameters]);

        $hooks($methodMeta, '@after');

        Event::fire('framework.dispatcher.afterhooks.after', [$action, $parameters]);

        // unset controller
        Registry::erase('controller');
    }

}
