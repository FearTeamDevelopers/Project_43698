<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Report
 *
 * @author Tomy
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
     * @label teaser
     */
    protected $_shortBody;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate required, html, max(30000)
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
     * @validate required, path, max(250)
     * @label photo path
     */
    protected $_imgMain;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate required, path, max(250)
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
     * @length 255
     * 
     * @validate alphanumeric, max(250)
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
     * Method replace specific strings whit their equivalent html tags
     * 
     * @param Model $content
     * @param type $parsedField
     * @return \Model
     */
    private function _parseContentBody($parsedField = 'body')
    {
        preg_match_all('/\(\!(photo|read|gallery|document)_[0-9a-z]+[a-z_]*\!\)/', $this->$parsedField, $matches);
        $m = array_shift($matches);

        foreach ($m as $match) {
            $match = str_replace(array('(!', '!)'), '', $match);

            if ($match == 'read_more' || $match == 'gallery' || $match == 'document') {
                $float = '';
                list($type, $id) = explode('_', $match);
            } else {
                list($type, $id, $float) = explode('_', $match);
            }

            $body = $this->$parsedField;
            if ($type == 'photo') {
                $photo = App_Model_Photo::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('photoName', 'imgMain', 'imgThumb')
                );

                if ($float == 'left') {
                    $floatClass = 'class="left-10"';
                } elseif ($float == 'right') {
                    $floatClass = 'class="right-10"';
                } else {
                    $floatClass = '';
                }

                $tag = "<a data-lightbox=\"img\" data-title=\"{$photo->photoName}\" "
                        . "href=\"{$photo->imgMain}\" title=\"{$photo->photoName}\">"
                        . "<img src=\"{$photo->imgThumb}\" {$floatClass} height=\"200px\" alt=\"Hastrman\"/></a>";

                $body = str_replace("(!photo_{$id}_{$float}!)", $tag, $body);

                $this->$parsedField = $body;
            }

            if ($type == 'document') {
                $doc = App_Model_Document::first(
                                array(
                            'id = ?' => $id,
                            'active = ?' => true
                                ), array('filename', 'filepath', 'description')
                );

                $tag = "<a href=\"{$doc->filepath}\" target=_blank>{$doc->description}</a>";

                $body = str_replace("(!document_{$id}!)", $tag, $body);
                $this->$parsedField = $body;
            }

            if ($type == 'gallery') {
                $gallery = App_Model_Gallery::first(array('isPublic = ?' => true, 'active = ?' => true, 'id = ?' => $id));
                
                $tag = "<a href=\"/galerie/r/{$gallery->getUrlKey()}\">{$gallery->getTitle()}</a>";
                $body = str_replace("(!gallery_{$id}!)", $tag, $body);
                $this->$parsedField = $body;
            }

            if ($type == 'read') {
                $tag = "<a href=\"/reportaze/r/{$this->getUrlKey()}\" class=\"news-read-more\">[Celý článek]</a>";
                $body = str_replace("(!read_more!)", $tag, $body);
                $this->$parsedField = $body;
            }
        }

        return $this;
    }
    
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
        $reports = self::initialize($query);

        return $reports;
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

        $reports = self::initialize($query);

        return $reports;
    }
    
    /**
     * Called from app module
     * @param type $limit
     * @return type
     */
    public static function fetchWithLimitParsed($limit = 10, $page = 1)
    {
        $query = self::getQuery(array('rp.*'))
                ->join('tb_user', 'rp.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->order('rp.created', 'desc')
                ->where('rp.active = ?', true)
                ->where('rp.approved = ?', true)
                ->where('rp.expirationDate >= ?', date('Y-m-d H:i:s'))
                ->limit((int)$limit, (int)$page);

        $reports = self::initialize($query);
        
        if($reports !== null){
            foreach($reports as $key => $val){
                $reports[$key] = $val->_parseContentBody('shortBody');
            }
        }

        return $reports;
    }

    /**
     * Called from app module
     * @param type $limit
     * @return type
     */
    public static function fetchOldWithLimitParsed($limit = 10, $page = 1)
    {
        $query = self::getQuery(array('rp.*'))
                ->join('tb_user', 'rp.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->order('rp.created', 'desc')
                ->where('rp.active = ?', true)
                ->where('rp.approved = ?', true)
                ->limit((int)$limit, (int)$page);

        $reports = self::initialize($query);
        
        if($reports !== null){
            foreach($reports as $key => $val){
                $reports[$key] = $val->_parseContentBody('shortBody');
            }
        }

        return $reports;
    }
    
    /**
     * Called from app module
     * @param type $urlKey
     * @return type
     */
    public static function fetchByUrlKeyParsed($urlKey)
    {
        $report = self::first(array('active = ?' => true, 'approved' => 1, 'urlKey = ?' => $urlKey));
        
        if($report !== null){
            $report = $report->_parseContentBody('body');
        }

        return $report;
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
