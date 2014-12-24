<?php

use THCFrame\Model\Model;

/**
 * 
 */
class App_Model_Report extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'rp';

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
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_approved;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_archive;

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
     * @label nazev
     */
    protected $_title;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(1000)
     * @label teaser
     */
    protected $_shortBody;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html
     * @label text
     */
    protected $_body;

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
     * @type tinyint
     * 
     * @validate numeric, max(2)
     * @label pořadí
     */
    protected $_rank;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * 
     * @validate alphanumeric, max(60)
     * @label název fotky
     */
    protected $_photoName;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label photo path
     */
    protected $_imgMain;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate path, max(250)
     * @label thumb path
     */
    protected $_imgThumb;

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
     * @type text
     * @length 150
     * 
     * @validate alphanumeric, max(150)
     * @label meta-název
     */
    protected $_metaTitle;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric
     * @label meta-popis
     */
    protected $_metaDescription;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 255
     * 
     * @validate alphanumeric, max(255)
     * @label meta-image
     */
    protected $_metaImage;

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
    protected $_fbLikeUrl;

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
        $query = self::getQuery(array('rp.*'))
                ->join('tb_user', 'rp.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'));
        
        return self::initialize($query);
    }

    /**
     * Called from admin module
     * @return array
     */
    public static function fetchWithLimit($limit = 10)
    {
        $query = self::getQuery(array('rp.*'))
                ->join('tb_user', 'rp.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->order('rp.created', 'desc')
                ->limit((int)$limit);

        return self::initialize($query);
    }
    
    /**
     * Called from app module
     * @param type $limit
     * @return type
     */
    public static function fetchActiveWithLimit($limit = 10, $page = 1)
    {
        $reports = self::all(array('active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false), 
                array('urlKey', 'userAlias', 'title', 'shortBody', 'created', 'imgMain', 'imgThumb', 'photoName'), 
                array('rank' => 'desc','created' => 'desc'), 
                $limit, $page
        );
        
        return $reports;
    }

    /**
     * Called from app module
     * @param type $limit
     * @return type
     */
    public static function fetchOldWithLimit($limit = 10, $page = 1)
    {
        $reports = self::all(array('active = ?' => true, 'approved = ?' => 1), 
                array('urlKey', 'userAlias', 'title', 'shortBody', 'created', 'imgMain', 'imgThumb', 'photoName'), 
                array('rank' => 'desc','created' => 'desc'), 
                $limit, $page
        );
        
        return $reports;
    }
    
    /**
     * Called from app module
     * @param type $urlKey
     * @return type
     */
    public static function fetchByUrlKey($urlKey)
    {
        return self::first(array('active = ?' => true, 'approved' => 1, 'urlKey = ?' => $urlKey));
    }
    
    /**
     * 
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_imgMain)) {
                return APP_PATH . $this->_imgMain;
            } elseif (file_exists('.' . $this->_imgMain)) {
                return '.' . $this->_imgMain;
            } elseif (file_exists('./' . $this->_imgMain)) {
                return './' . $this->_imgMain;
            }
        } else {
            return $this->_imgMain;
        }
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_imgThumb)) {
                return APP_PATH . $this->_imgThumb;
            } elseif (file_exists('.' . $this->_imgThumb)) {
                return '.' . $this->_imgThumb;
            } elseif (file_exists('./' . $this->_imgThumb)) {
                return './' . $this->_imgThumb;
            }
        } else {
            return $this->_imgThumb;
        }
    }

}
