<?php

namespace Admin\Model;

use THCFrame\Model\Model;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class ActionHistoryModel extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'ach';

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
     * @label id zdroje
     */
    protected $_originId;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(8)
     * @label id autora
     */
    protected $_createdBy;
    
    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate numeric, max(8)
     * @label id editora
     */
    protected $_editedBy;

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
     * @validate required, html
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
     * @length 12
     * 
     * @validate date, max(12)
     * @label datum začátek
     */
    protected $_startDate;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 12
     * 
     * @validate date, max(12)
     * @label datum konec
     */
    protected $_endDate;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 10
     * 
     * @validate time, max(10)
     * @label čas začátek
     */
    protected $_startTime;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 10
     * 
     * @validate time, max(10)
     * @label čas konec
     */
    protected $_endTime;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 350
     * 
     * @validate alphanumeric, max(350)
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
     * @length 22
     * 
     * @validate datetime, max(22)
     */
    protected $_created;

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
    }

    /**
     * 
     * @return array
     */
    public static function fetchAll()
    {
        $query = self::getQuery(array('ac.*'))
                ->join('tb_user', 'ac.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'));
        
        return self::initialize($query);
    }

    /**
     * Called from admin module
     * 
     * @return array
     */
    public static function fetchWithLimit($limit = 10)
    {
        $query = self::getQuery(array('ac.*'))
                ->join('tb_user', 'ac.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->order('ac.created', 'desc')
                ->limit((int)$limit);

        return self::initialize($query);
    }
    
    /**
     * 
     * @param \App\Model\ActionModel $action
     */
    public static function createFromSource(\App\Model\ActionModel $action)
    {
        $sec = Registry::get('security');
        $user = $sec->getUser();
        
        $historyRecord = new self(array(
            'originId' => $action->getId(),
            'createdBy' => $action->getUserId(),
            'editedBy' => $user->getId(),
            'title' => $action->getTitle(),
            'userAlias' => $action->getUserAlias(),
            'urlKey' => $action->getUrlKey(),
            'active' => $action->getActive(),
            'approved' => $action->getApproved(),
            'archive' => $action->getArchive(),
            'shortBody' => $action->getShortBody(),
            'body' => $action->getBody(),
            'rank' => $action->getRank(),
            'startDate' => $action->getStartDate(),
            'endDate' => $action->getEndDate(),
            'startTime' => $action->getStartTime(),
            'endTime' => $action->getEndTime(),
            'keywords' => $action->getKeywords(),
            'metaTitle' => $action->getMetaTitle(),
            'metaDescription' => $action->getMetaDescription()
        ));
        
        if($historyRecord->validate()){
            $id = $historyRecord->save();
            Event::fire('admin.log', array('success', 'Action history id: '. $id));
        }else{
            Event::fire('admin.log', array('fail', 'Action history errors: ' . json_encode($historyRecord->getErrors())));
        }
    }
}