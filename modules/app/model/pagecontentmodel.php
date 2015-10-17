<?php

namespace App\Model;

use App\Model\Basic\BasicPagecontentModel;

/**
 * 
 */
class PageContentModel extends BasicPagecontentModel
{

    /**
     * @readwrite
     */
    protected $_alias = 'co';

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setActive(true);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * @param type $urlKey
     */
    public static function fetchByUrlKey($urlKey)
    {
        return self::first(array('urlKey = ?' => $urlKey, 'active = ?' => true));
    }

}
