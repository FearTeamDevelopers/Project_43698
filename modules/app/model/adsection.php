<?php

use THCFrame\Model\Model;

/**
 * 
 */
class App_Model_AdSection extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'ads';

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
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_active;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 200
     * @unique
     * 
     * @validate required, alphanumeric, max(200)
     * @label url key
     */
    protected $_urlKey;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate required, alphanumeric, max(150)
     * @label název
     */
    protected $_title;
    
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
     * @readwrite
     * @var type 
     */
    protected $_adTenderCount;
    
    /**
     * @readwrite
     * @var type 
     */
    protected $_adDemandCount;

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
     * 
     * @return type
     */
    public static function fetchAll()
    {
        $sections = self::all(
                array(),
                array('ads.*', 
                    '(SELECT COUNT(adv.id) FROM `tb_advertisement` adv WHERE adv.sectionId = ads.id AND adv.type="tender")' => 'adTenderCount',
                    '(SELECT COUNT(adv.id) FROM `tb_advertisement` adv WHERE adv.sectionId = ads.id AND adv.type="demand")' => 'adDemandCount')
                );
        
        return $sections;
    }
    
    /**
     * 
     * @return type
     */
    public static function fetchAllActive()
    {
        $sections = self::all(
                array('ads.active = ?' => true),
                array('ads.*', 
                    '(SELECT COUNT(adv.id) FROM `tb_advertisement` adv WHERE adv.sectionId = ads.id AND adv.type="tender" AND adv.active=1)' => 'adTenderCount',
                    '(SELECT COUNT(adv.id) FROM `tb_advertisement` adv WHERE adv.sectionId = ads.id AND adv.type="demand" AND adv.active=1)' => 'adDemandCount',
                    )
                );
        
        return $sections;
    }

}
