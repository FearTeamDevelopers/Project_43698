<?php

use THCFrame\Model\Model;

/**
 * 
 */
class App_Model_Advertisement extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'adv';

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
     * @validate numeric, max(8)
     * @label autor
     */
    protected $_userId;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(8)
     * @label sekce
     */
    protected $_sectionId;

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
     * @length 40
     * 
     * @validate required, alphanumeric, max(40)
     * @label jedinečný identifikátor
     */
    protected $_uniqueKey;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 15
     * 
     * @validate required, alpha, max(15)
     * @label typ
     */
    protected $_adtype;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 80
     * 
     * @validate alphanumeric, max(80)
     * @label alias autora
     */
    protected $_userAlias;

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
     * @type text
     * @length 256
     * 
     * @validate required, html, max(5000)
     * @label obsah
     */
    protected $_content;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 22
     * 
     * @validate date, max(22)
     * @label zobrazovat do
     */
    protected $_expirationDate;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     * @label keywords
     */
    protected $_keywords;

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
     */
    protected $_messageCount;

    /**
     * @readwrite
     */
    protected $_messages;
    
    /**
     * @readwrite
     */
    protected $_images;


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
     * Called from admin module
     * 
     * @return array
     */
    public static function fetchAll()
    {
        $query = self::getQuery(array('adv.*', '(SELECT COUNT(adm.id) FROM `tb_admessage` adm where adm.adId = adv.id)' => 'messageCount'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                        array('ads.title' => 'sectionTitle'));
        
        $ads = self::initialize($query);
        
        return $ads;
    }

    /**
     * Called from admin module
     * 
     * @param type $id
     * @return type
     */
    public static function fetchById($id)
    {
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                        array('ads.title' => 'sectionTitle'))
                ->where('adv.id = ?', (int) $id);
        
        $adArr = self::initialize($query);
        $ad = array_shift($adArr);

        if ($ad !== null) {
            $ad->messages = App_Model_AdMessage::all(array('adId = ?' => $ad->getId()));
            $ad->images = App_Model_AdImage::all(array('adId = ?' => $ad->getId()));
        }

        return $ad;
    }

    /**
     * Called from app module
     * 
     * @return type
     */
    public static function fetchLatestFive()
    {
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                        array('ads.title' => 'sectionTitle'))
                ->order('adv.created', 'desc')
                ->limit(5);
        
        $ads = self::initialize($query);
        
        return $ads;
    }
    
    /**
     * Called from app module
     * 
     * @param type $type
     * @param type $page
     * @return type
     */
    
    public static function fetchActiveByType($type, $page = 1)
    {
        if($type != 'tender' || $type != 'demand'){
            return null;
        }
        
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                        array('ads.title' => 'sectionTitle'))
                ->order('adv.created', 'desc')
                ->where('adv.active = ?', true)
                ->where('adv.type = ?', $type)
                ->limit(15, $page);
        
        $ads = self::initialize($query);
        
        return $ads;
    }
    
    /**
     * Called from app module
     * 
     * @param type $type
     * @param type $page
     * @return type
     */
    
    public static function fetchActiveByTypeSection($type, $section, $page = 1)
    {
        if($type != 'tender' || $type != 'demand'){
            return null;
        }
        
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                        array('ads.title' => 'sectionTitle'))
                ->where('ads.urlKey = ?', $section)
                ->where('adv.active = ?', true)
                ->where('adv.type = ?', $type)
                ->order('adv.created', 'desc')
                ->limit(15, $page);
        
        $ads = self::initialize($query);
        
        return $ads;
    }
    
    /**
     * Called from app module
     * 
     * @param type $urlkey
     * @return type
     */
    public static function fetchActiveByKey($urlkey)
    {
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                        array('ads.title' => 'sectionTitle'))
                ->where('adv.uniqueKey = ?', $urlkey)
                ->where('adv.active = ?', true);
        
        $adArr = self::initialize($query);
        $ad = array_shift($adArr);
        
        return $ad;
    }
    
    /**
     * Called from app module
     * 
     * @param type $userId
     * @return type
     */
    public static function fetchActiveByUser($userId)
    {
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                        array('ads.title' => 'sectionTitle'))
                ->where('adv.userId = ?', $userId)
                ->where('adv.active = ?', true);
        
        $ads = self::initialize($query);
        
        return $ads;
    }
}
