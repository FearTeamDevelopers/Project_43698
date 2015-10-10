<?php

namespace THCFrame\Security\Model;

use THCFrame\Model\Model;

/**
 * Description of FhsScannedModel
 *
 * @author Tomy
 */
class FhsScannedModel extends Model
{

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(9)
     * @label changes
     */
    protected $_changes;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     * @label scanned
     */
    protected $_scanned;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 20
     * 
     * @validate alphanumeric, max(20)
     * @label account
     */
    protected $_acct;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_modified;

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
    
    public static function getLastScann()
    {
        return self::first(array(),array('*'), array('created' => 'desc'));
    }

}
