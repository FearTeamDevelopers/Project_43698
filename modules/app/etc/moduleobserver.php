<?php

namespace App\Etc;

use THCFrame\Registry\Registry;
use THCFrame\Events\SubscriberInterface;

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
            'app.log' => 'appLog'
        );
    }

    /**
     * 
     * @param array $params
     */
    public function appLog()
    {
        $params = func_get_args();

        $router = Registry::get('router');
        $route = $router->getLastRoute();

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
            'userId' => 'annonymous',
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

}