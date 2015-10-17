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
}
