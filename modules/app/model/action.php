<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Action
 *
 * @author Tomy
 */
class App_Model_Action extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'ac';

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
        $query = self::getQuery(array('ac.*'))
                ->join('tb_user', 'ac.userId = us.id', 'us', array('us.firstname', 'us.lastname'));
        $actions = self::initialize($query);

        return $actions;
    }

    /**
     * 
     * @return array
     */
    public static function fetchLastTen()
    {
        $query = self::getQuery(array('ac.*'))
                ->join('tb_user', 'ac.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                ->order('ac.created', 'desc')
                ->limit(10);

        $actions = self::initialize($query);

        return $actions;
    }

}
