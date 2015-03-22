<?php

namespace Admin\Model;

use THCFrame\Model\Model;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class NewsHistoryModel extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'nwh';

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
        $query = self::getQuery(array('nw.*'))
                ->join('tb_user', 'nw.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'));

        return self::initialize($query);
    }

    /**
     * Called from admin module
     * @return array
     */
    public static function fetchWithLimit($limit = 10, $page = 1)
    {
        $query = self::getQuery(array('nw.*'))
                ->join('tb_user', 'nw.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'))
                ->order('nw.created', 'desc')
                ->limit((int) $limit, $page);

        return self::initialize($query);
    }

    /**
     * 
     * @param \App\Model\NewsModel $news
     */
    public static function createFromSource(\App\Model\NewsModel $news)
    {
        $sec = Registry::get('security');
        $user = $sec->getUser();

        $historyRecord = new self(array(
            'originId' => $news->getId(),
            'createdBy' => $news->getUserId(),
            'editedBy' => $user->getId(),
            'title' => $news->getTitle(),
            'userAlias' => $news->getUserAlias(),
            'urlKey' => $news->getUrlKey(),
            'active' => $news->getActive(),
            'approved' => $news->getApproved(),
            'archive' => $news->getArchive(),
            'shortBody' => $news->getShortBody(),
            'body' => $news->getBody(),
            'rank' => $news->getRank(),
            'keywords' => $news->getKeywords(),
            'metaTitle' => $news->getMetaTitle(),
            'metaDescription' => $news->getMetaDescription()
        ));

        if ($historyRecord->validate()) {
            $id = $historyRecord->save();
            Event::fire('admin.log', array('success', 'News history id: ' . $id));
        } else {
            Event::fire('admin.log', array('fail', 'News history errors: ' . json_encode($historyRecord->getErrors())));
        }
    }

}
