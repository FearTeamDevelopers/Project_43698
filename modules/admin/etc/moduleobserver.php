<?php

namespace Admin\Etc;

use Admin\Model\AdminLogModel;
use THCFrame\Model\Exception\Validation;
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
    public function getSubscribedEvents(): array
    {
        return [
            'admin.log' => 'adminLog',
        ];
    }

    /**
     * @throws Validation
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public function adminLog(): void
    {
        $params = func_get_args();

        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $security = Registry::get('security');
        $user = $security->getUser();
        if ($user === null) {
            $userId = 'annonymous';
        } else {
            $userId = $user->getWholeName() . ':' . $user->getId();
        }

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

        $log = new AdminLogModel([
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
                'type' => 'adminLog',
                'result' => $result,
                'module' => $module,
                'controller' => $controller,
                'action' => $action,
                'params' => $paramStr]
            );
        }
    }

}
