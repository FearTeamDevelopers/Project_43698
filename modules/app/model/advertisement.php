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
    protected $_adType;

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
     * @type decimal
     * 
     * @validate required, numeric
     * @label cena
     */
    protected $_price;

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
     * @type boolean
     * 
     * @validate max(3)
     */
    protected $_hasAvailabilityRequest;
    
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
            $this->setHasAvailabilityRequest(false);
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
        $query = self::getQuery(array('adv.id','adv.title', 'adv.adType','adv.expirationDate',
                                'adv.active','adv.created','adv.hasAvailabilityRequest',
                                '(SELECT COUNT(adm.id) FROM `tb_admessage` adm where adm.adId = adv.id)' => 'messageCount'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                        array('ads.title' => 'sectionTitle'));

        return self::initialize($query);
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

        if (null !== $ad) {
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
    public static function fetchAdsActive($adsPerPage = 10, $page = 1)
    {
        $query = self::getQuery(array('adv.id', 'adv.uniqueKey', 'adv.adType', 'adv.userAlias', 
                                'adv.title', 'adv.price', 'adv.created'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', 
                        array('ads.title' => 'sectionTitle'))
                ->where('adv.active = ?', true)
                ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                ->order('adv.created', 'desc')
                ->limit((int)$adsPerPage, (int) $page);

        return self::initialize($query);
    }

    /**
     * Called from app module
     * 
     * @param type $type
     * @param type $page
     * @return type
     */
    public static function fetchActiveByType($type, $adsPerPage = 10, $page = 1)
    {
        if ($type == 'tender' || $type == 'demand') {
            $query = self::getQuery(array('adv.id', 'adv.uniqueKey', 'adv.adType', 'adv.userAlias', 
                                'adv.title', 'adv.price', 'adv.created'))
                    ->join('tb_user', 'adv.userId = us.id', 'us', 
                            array('us.firstname', 'us.lastname'))
                    ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', 
                            array('ads.title' => 'sectionTitle'))
                    ->where('adv.active = ?', true)
                    ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                    ->where('adv.adType = ?', $type)
                    ->order('adv.created', 'desc')
                    ->limit((int)$adsPerPage, (int)$page);

            $ads = self::initialize($query);
            
            if(null !== $ads){
                foreach($ads as &$ad){
                    $ad->images = App_Model_AdImage::all(array('adId = ?' => $ad->getId()));
                }
            }
            
            return $ads;
        }else{
            return null;
        }
    }

    /**
     * 
     * @param type $type
     * @return type
     */
    public static function countActiveByType($type)
    {
        if ($type == 'tender' || $type == 'demand') {
            return self::count(array('active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s'), 'type = ?' => $type), array('id'));
        }else{
            return null;
        }
    }

    /**
     * Called from app module
     * 
     * @param type $type
     * @param type $page
     * @return type
     */
    public static function fetchActiveByTypeSection($type, $section, $adsPerPage = 10, $page = 1)
    {
        if ($type == 'tender' || $type == 'demand') {
            $query = self::getQuery(array('adv.id', 'adv.uniqueKey', 'adv.adType', 'adv.userAlias', 
                                'adv.title', 'adv.price', 'adv.created'))
                    ->join('tb_user', 'adv.userId = us.id', 'us', 
                            array('us.firstname', 'us.lastname'))
                    ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads',
                            array('ads.title' => 'sectionTitle'))
                    ->where('ads.urlKey = ?', $section)
                    ->where('adv.active = ?', true)
                    ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                    ->where('adv.adType = ?', $type)
                    ->order('adv.created', 'desc')
                    ->limit((int)$adsPerPage, (int)$page);

            $ads = self::initialize($query);
            
            if(null !== $ads){
                foreach($ads as &$ad){
                    $ad->images = App_Model_AdImage::all(array('adId = ?' => $ad->getId()));
                }
            }
            
            return $ads;
        }else{
            return null;
        }
    }

    /**
     * 
     * @param type $type
     * @param type $section
     * @return type
     */
    public static function countActiveByTypeSection($type, $section)
    {
        if ($type == 'tender' || $type == 'demand') {
            $query = self::getQuery(array('COUNT(adv.id) as count'))
                    ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', 
                            array('ads.title' => 'sectionTitle'))
                    ->where('ads.urlKey = ?', $section)
                    ->where('adv.active = ?', true)
                    ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                    ->where('adv.adType = ?', $type);

            $arr = self::initialize($query);
            $obj = array_shift($arr);
            return (int)$obj->count;
        } else {
            return null;
        }
    }

    /**
     * Called from app module
     * 
     * @param type $uniquekey
     * @return type
     */
    public static function fetchActiveByKey($uniquekey)
    {
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname', 'us.email'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', 
                        array('ads.title' => 'sectionTitle'))
                ->where('adv.uniqueKey = ?', $uniquekey)
                ->where('adv.active = ?', true);

        $adArr = self::initialize($query);
        $ad = array_shift($adArr);

        if (null !== $ad) {
            $ad->messages = App_Model_AdMessage::all(array('adId = ?' => $ad->getId()));
            $ad->images = App_Model_AdImage::all(array('adId = ?' => $ad->getId()));
        }
        
        return $ad;
    }

    /**
     * Called from app module
     * 
     * @param type $userId
     * @return type
     */
    public static function fetchActiveByUser($userId, $adsPerPage = 10, $page = 1)
    {
        $query = self::getQuery(array('adv.id', 'adv.uniqueKey', 'adv.adType', 'adv.userAlias', 
                                'adv.title', 'adv.price', 'adv.created'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', 
                        array('ads.title' => 'sectionTitle'))
                ->where('adv.userId = ?', $userId)
                ->where('adv.active = ?', true)
                ->limit((int)$adsPerPage, (int)$page);

        $ads = self::initialize($query);
        if(null !== $ads){
            foreach($ads as &$ad){
                $ad->images = App_Model_AdImage::all(array('adId = ?' => $ad->getId()));
            }
        }

        return $ads;
    }
    
    /**
     * 
     * @param type $userId
     * @return type
     */
    public static function countActiveByUser($userId)
    {
        return self::count(array('active = ?' => true, 'userId = ?' => (int)$userId), array('id'));
    }

}
