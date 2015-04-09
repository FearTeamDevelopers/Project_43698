<?php

namespace Admin\Etc;

use THCFrame\Registry\Registry;
use THCFrame\Events\SubscriberInterface;
use THCFrame\Security\Model\SecLogModel;

/**
 * Module specific observer class
 */
class ModuleObserver implements SubscriberInterface
{

    /**
     * 
     * @return type
     */
    public function getSubscribedEvents()
    {
        return array(
            'admin.log' => 'adminLog',
        );
    }

    /**
     * 
     * @param array $params
     */
    public function adminLog()
    {
        $params = func_get_args();

        $router = Registry::get('router');
        $route = $router->getLastRoute();

        $security = Registry::get('security');
        $userId = $security->getUser()->getWholeName();

        $module = $route->getModule();
        $controller = $route->getController();
        $action = $route->getAction();

        if (!empty($params)) {
            $result = array_shift($params);

            $paramStr = '';
            if (!empty($params)) {
                $paramStr = join(', ', $params);
            }
        } else {
            $result = 'fail';
            $paramStr = '';
        }

        $log = new \Admin\Model\AdminLogModel(array(
            'userId' => $userId,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'result' => $result,
            'params' => $paramStr
        ));

        if ($log->validate()) {
            $log->save();
        }
    }

    /**
     * 
     */
    public function secLog()
    {
        $params = func_get_args();
    }

}
