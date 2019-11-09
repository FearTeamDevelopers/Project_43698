<?php

namespace Search\Etc;

use THCFrame\Registry\Registry;
use THCFrame\Events\SubscriberInterface;
use THCFrame\Request\RequestMethods;
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
            'search.log' => 'searchLog',
        ];
    }

    /**
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     * @throws \THCFrame\Model\Exception\Validation
     */
    public function searchLog()
    {
        $params = func_get_args();

        $security = Registry::get('security');
        $user = $security->getUser();
        if ($user === null) {
            $userId = 'searchjob';
        } else {
            $userId = $user->getWholeName() . ':' . $user->getId();
        }

        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $result = array_shift($params);

            $paramStr = '';
            if (!empty($params)) {
                $paramStr = implode(', ', $params);
            }
        } else {
            $result = 'fail';
            $paramStr = '';
        }

        $log = new \Search\Model\AdminLogModel([
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
        }else{
            Core::getLogger()->info('{type} {result} /{module}/{controller}/{action} {params}', [
                'type' => 'searchLog',
                'result' => $result,
                'module' => $module,
                'controller' => $controller,
                'action' => $action,
                'params' => $paramStr]
            );
        }
    }

}
