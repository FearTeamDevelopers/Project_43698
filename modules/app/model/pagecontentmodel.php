<?php

namespace App\Model;

use App\Model\Basic\BasicPagecontentModel;
use Search\Model\IndexableInterface;

/**
 *
 */
class PageContentModel extends BasicPagecontentModel implements IndexableInterface
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
        return self::first(['urlKey = ?' => $urlKey, 'active = ?' => true]);
    }

    /**
     * Check whether unique identifier already exist or not
     *
     * @param type $urlKey
     * @return boolean
     */
    public static function checkUrlKey($urlKey)
    {
        $status = self::first(['urlKey = ?' => $urlKey]);

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }

}
