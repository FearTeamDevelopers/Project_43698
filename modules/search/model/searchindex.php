<?php

use THCFrame\Model\Model;

/**
 * 
 */
class Search_Model_Searchindex extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'si';

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
     * @type text
     * @length 100
     * 
     * @validate alpha, max(100)
     * @label source model
     */
    protected $_sourceModel;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 100
     * 
     * @validate required, alphanumeric, max(100)
     * @label word
     */
    protected $_sword;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * 
     * @validate path, max(255)
     * @label path to source
     */
    protected $_pathToSource;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate path, max(150)
     * @label source title
     */
    protected $_sourceTitle;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(8)
     * @label occurence
     */
    protected $_occurence;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(8)
     * @label weight
     */
    protected $_weight;
    
    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
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

}
