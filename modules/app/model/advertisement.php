<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Advertisement
 *
 * @author Tomy
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
     * @length 15
     * 
     * @validate required, alpha, max(15)
     * @label typ
     */
    protected $_type;

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
     * @validate alpha, max(150)
     * @label typ
     */
    protected $_section;

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
     * 
     * @return array
     */
    public static function fetchAll()
    {
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'));
        
        $ads = self::initialize($query);
        
        if ($ads !== null) {
            foreach ($ads as $key => $ad) {
                $ad->messageCount = App_Model_AdMessage::count(array('adId = ?' => $ad->getId()));
                $ads[$key] = $ad;
            }
        }
        
        return $ads;
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public static function fetchById($id)
    {
        $query = self::getQuery(array('adv.*'))
                ->join('tb_user', 'adv.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
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
     * 
     * @param type $type
     */
    public static function fetchActiveByType($type = 'tender')
    {
        
    }
}
