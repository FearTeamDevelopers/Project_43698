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
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'api.log' => 'apiLog',
        ];
    }

    /**
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     * @throws \THCFrame\Model\Exception\Validation
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

        $log = new \Admin\Model\AdminLogModel([
            'userId' => $userId,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'result' => $result,
            'httpreferer' => RequestMethods::getHttpReferer(),
            'params' => $paramStr,
        ]);

        if ($log->validate()) {
            $log->save();
        } else {
            Core::getLogger()->info('{type} {result} /{module}/{controller}/{action} {params}', [
                'type' => 'apiLog',
                'result' => $result,
                'module' => $module,
                'controller' => $controller,
                'action' => $action,
                'params' => $paramStr]
            );
        }
    }

}
