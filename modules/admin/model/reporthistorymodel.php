<?php

namespace Admin\Model;

use THCFrame\Model\Model;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;

/**
 * 
 */
class ReportHistoryModel extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'rph';

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
     * @length 350
     * 
     * @validate max(350)
     * @label photo path
     */
    protected $_imgMain;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 350
     * 
     * @validate max(350)
     * @label thumb path
     */
    protected $_imgThumb;

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
     * @length 350
     * 
     * @validate alphanumeric, max(350)
     * @label meta-image
     */
    protected $_metaImage;

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
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * 
     * @param \App\Model\ReportModel $report
     */
    public static function createFromSource(\App\Model\ReportModel $report)
    {
        $sec = Registry::get('security');
        $user = $sec->getUser();

        $historyRecord = new self(array(
            'originId' => $report->getId(),
            'createdBy' => $report->getUserId(),
            'editedBy' => $user->getId(),
            'title' => $report->getTitle(),
            'userAlias' => $report->getUserAlias(),
            'urlKey' => $report->getUrlKey(),
            'active' => $report->getActive(),
            'approved' => $report->getApproved(),
            'archive' => $report->getArchive(),
            'shortBody' => $report->getShortBody(),
            'body' => $report->getBody(),
            'rank' => $report->getRank(),
            'keywords' => $report->getKeywords(),
            'metaTitle' => $report->getMetaTitle(),
            'metaDescription' => $report->getMetaDescription(),
            'metaImage' => $report->getMetaImage(),
            'photoName' => $report->getPhotoName(),
            'imgMain' => $report->getImgMain(),
            'imgThumb' => $report->getImgThumb()
        ));

        if ($historyRecord->validate()) {
            $id = $historyRecord->save();
            Event::fire('admin.log', array('success', 'Report history id: ' . $id));
        } else {
            Event::fire('admin.log', array('fail', 'Report history errors: ' . json_encode($historyRecord->getErrors())));
        }
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
