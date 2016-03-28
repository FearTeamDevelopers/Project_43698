<?php

namespace Api\Etc;

use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\SubscriberInterface;
use THCFrame\Core\Core;

/**
 * Module specific observer class.
 */
class ModuleObserver implements SubscriberInterface
{
    /**
     * @return type
     */
    public function getSubscribedEvents()
    {
        return array(
            'api.log' => 'apiLog',
        );
    }

    /**
     * @param array $params
     */
    public function apiLog()
    {
        $params = func_get_args();

        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $result = array_shift($params);
            $userId = (int) array_shift($params);

            $paramStr = '';
            if (!empty($params)) {
                $paramStr = implode(', ', $params);
            }
        } else {
            $result = 'fail';
            $userId = 'annonymous';
            $paramStr = '';
        }

        $log = new \Admin\Model\AdminLogModel(array(
            'userId' => $userId,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'result' => $result,
            'httpreferer' => RequestMethods::getHttpReferer(),
            'params' => $paramStr,
        ));

        Core::getLogger()->info('{type} {result} /{module}/{controller}/{action} {params}', array(
            'type' => 'apiLog',
            'result' => $result,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'params' => $paramStr)
        );

        if ($log->validate()) {
            $log->save();
        }
    }

}