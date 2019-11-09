<?php

namespace Admin\Model;

use Admin\Model\Basic\BasicAdminlogModel;

/**
 * Log ORM class.
 */
class AdminLogModel extends BasicAdminlogModel
{

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchErrorsFromLastWeek(): ?array
    {
        return self::all(['result = ?' => 'fail', 'created between date_sub(now(),INTERVAL 1 WEEK) and now()' => ''], ['*'], ['created' => 'desc']);
    }
}
