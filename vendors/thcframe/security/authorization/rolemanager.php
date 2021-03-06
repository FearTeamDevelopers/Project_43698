<?php

namespace THCFrame\Security\Authorization;

use THCFrame\Core\Base;
use THCFrame\Security\Exception;

/**
 * RoleManager manage access roles form config file
 */
class RoleManager extends Base
{

    /**
     * Array of all available roles
     * @readwrite
     * @var array
     */
    protected $_roles;

    /**
     * Object constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        foreach ($options as $value) {
            $start = strpos($value, '[');
            $end = strpos($value, ']');

            if ($start) {
                $role = substr($value, 0, $start);

                if (strtolower(substr($role, 0, 5)) != 'role_') {
                    throw new Exception\Role('Role name is not valid');
                }

                $extend = substr($value, $start + 1, ($end - $start - 1));
                $extendArr = explode(',', $extend);
                array_unshift($extendArr, $role);

                $trimedExtendArr = array_map('trim', $extendArr);

                $this->_roles[$role] = $trimedExtendArr;
            } else {
                $this->_roles[$value] = [$value];
            }
        }
    }

    /**
     * Return all roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->_roles;
    }

    /**
     * Check if required role exists and return it
     *
     * @param string $rolename
     * @return mixed
     */
    public function getRole($rolename)
    {
        if ($this->roleExist($rolename)) {
            return $this->_roles[$rolename];
        } else {
            return null;
        }
    }

    /**
     * Check if required role exists
     *
     * @param string $rolename
     * @return boolean
     */
    public function roleExist($rolename)
    {
        if (array_key_exists($rolename, $this->_roles)) {
            return true;
        } else {
            return false;
        }
    }

}
