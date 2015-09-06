<?php

namespace Admin\Model;

use THCFrame\Model\Model;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;

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
     * @label id editora
     */
    protected $_editedBy;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 30
     * 
     * @validate alphanumeric, max(30)
     * @label keywords
     */
    protected $_remoteAddr;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 150
     * 
     * @validate url, max(150)
     * @label referrer
     */
    protected $_referer;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 256
     * 
     * @validate alphanumeric
     * @label changes
     */
    protected $_changedData;

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
     * @return array
     */
    public static function fetchAll()
    {
        $query = self::getQuery(array('rph.*'))
                ->join('tb_user', 'rph.editedBy = us.id', 'us',
                        array('us.firstname', 'us.lastname'));

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     *
     * @return array
     */
    public static function fetchWithLimit($limit = 10)
    {
        $query = self::getQuery(array('rph.*'))
                ->join('tb_user', 'rph.editedBy = us.id', 'us',
                        array('us.firstname', 'us.lastname'))
                ->order('rph.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * Check differences between two objects.
     * 
     * @param \App\Model\ReportModel $original
     * @param \App\Model\ReportModel $edited
     */
    public static function logChanges(\App\Model\ReportModel $original, \App\Model\ReportModel $edited)
    {
        $sec = Registry::get('security');
        $user = $sec->getUser();

        $remoteAddr = RequestMethods::getClientIpAddress();
        $referer = RequestMethods::server('HTTP_REFERER');
        $changes = array();

        $reflect = new \ReflectionClass($original);
        $properties = $reflect->getProperties();
        $className = get_class($original);

        if (empty($properties)) {
            return;
        }

        foreach ($properties as $key => $value) {
            if ($value->class == $className) {
                $propertyName = $value->getName();
                $getProperty = 'get'.ucfirst(str_replace('_', '', $value->getName()));

                if (trim((string) $original->$getProperty()) !== trim((string) $edited->$getProperty())) {
                    $changes[$propertyName] = $original->$getProperty();
                }
            }
        }

        $historyRecord = new self(array(
            'originId' => $original->getId(),
            'editedBy' => $user->getId(),
            'remoteAddr' => $remoteAddr,
            'referer' => $referer,
            'changedData' => json_encode($changes),
        ));

        if ($historyRecord->validate()) {
            $historyRecord->save();
            Event::fire('admin.log', array('success', 'Report '.$original->getId().' changes saved'));
        } else {
            Event::fire('admin.log', array('fail', 'Report history errors: '.json_encode($historyRecord->getErrors())));
        }
    }

    /**
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH.$this->_imgMain)) {
                return APP_PATH.$this->_imgMain;
            } elseif (file_exists('.'.$this->_imgMain)) {
                return '.'.$this->_imgMain;
            } elseif (file_exists('./'.$this->_imgMain)) {
                return './'.$this->_imgMain;
            }
        } else {
            return $this->_imgMain;
        }
    }

    /**
     * @return type
     */
    public function getUnlinkThumbPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH.$this->_imgThumb)) {
                return APP_PATH.$this->_imgThumb;
            } elseif (file_exists('.'.$this->_imgThumb)) {
                return '.'.$this->_imgThumb;
            } elseif (file_exists('./'.$this->_imgThumb)) {
                return './'.$this->_imgThumb;
            }
        } else {
            return $this->_imgThumb;
        }
    }
}
