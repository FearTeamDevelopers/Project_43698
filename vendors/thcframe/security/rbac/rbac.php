<?php

namespace THCFrame\Security\Rbac;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Security\Exception;

class Rbac
{

    private $_permissionList = [];
    private $_roleList = [];
    private $_graph;
    private static $_instance = null;

    private function __construct()
    {
        $this->generateGraph();
    }

    private function isValidPermission($permission)
    {

    }

    private function isValidResource($module, $controller, $action)
    {

    }

    private function generateGraph()
    {

    }

    /**
     * @return Rbac|null
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @param $permission
     */
    public function hasPermission($permission)
    {
        /*
         * userroles
         * result = false
         * foreach userroles
         *
         *  role getpermissions
         *  foreach permissions
         *      permission is allowed or is denied
         *      result = true or false
         */
    }

    /**
     * @param $module
     * @param $controller
     * @param $action
     * @return bool
     */
    public function checkResource($module, $controller, $action)
    {
        if ($this->isValidResource($module, $controller, $action)) {
            return true;
        }

        return false;
    }

}
