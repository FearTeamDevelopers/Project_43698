<?php

use THCFrame\Registry\Registry;
use THCFrame\Events\SubscriberInterface;

/**
 * Module specific observer class
 */
class App_Etc_Observer implements SubscriberInterface
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

        $log = new Admin_Model_AdminLog(array(
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
