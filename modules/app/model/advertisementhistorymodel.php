<?php

namespace App\Model;

use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;
use App\Model\Basic\BasicAdvertisementhistoryModel;

/**
 * 
 */
class AdvertisementHistoryModel extends BasicAdvertisementhistoryModel
{

    /**
     * @readwrite
     */
    protected $_alias = 'adh';

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
        $query = self::getQuery(['ac.*'])
                ->join('tb_user', 'ac.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     * 
     * @return array
     */
    public static function fetchWithLimit($limit = 10)
    {
        $query = self::getQuery(['ac.*'])
                ->join('tb_user', 'ac.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->order('ac.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * Check differences between two objects.
     * 
     * @param \App\Model\ActionModel $original
     * @param \App\Model\ActionModel $edited
     */
    public static function logChanges(\App\Model\AdvertisementModel $original, \App\Model\AdvertisementModel $edited)
    {
        $sec = Registry::get('security');
        $user = $sec->getUser();

        $remoteAddr = RequestMethods::getClientIpAddress();
        $referer = RequestMethods::server('HTTP_REFERER');
        $changes = [];

        $reflect = new \ReflectionClass($original);
        $properties = $reflect->getProperties();
        $className = get_class($original);

        if (empty($properties)) {
            return;
        }

        foreach ($properties as $key => $value) {
            if (!preg_match('#.*@column.*#s', $value->getDocComment())) {
                continue;
            }
            if ($value->class == $className) {
                $propertyName = $value->getName();
                $getProperty = 'get' . ucfirst(str_replace('_', '', $value->getName()));

                if (trim((string) $original->$getProperty()) !== trim((string) $edited->$getProperty())) {
                    $changes[$propertyName] = $original->$getProperty();
                }
            }
        }

        $historyRecord = new self([
            'originId' => $original->getId(),
            'editedBy' => $user->getId(),
            'remoteAddr' => $remoteAddr,
            'referer' => $referer,
            'changedData' => json_encode($changes),
        ]);

        if ($historyRecord->validate()) {
            $historyRecord->save();
            Event::fire('app.log', ['success', 'Advertisement ' . $original->getId() . ' changes saved']);
        } else {
            Event::fire('app.log', ['fail', 'Advertisement history errors: ' . json_encode($historyRecord->getErrors())]);
        }
    }

}
