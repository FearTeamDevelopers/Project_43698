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
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
     * @param int $limit
     * @return array
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
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
     * @param AdvertisementModel $original
     * @param AdvertisementModel $edited
     * @throws \ReflectionException
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     * @throws \THCFrame\Model\Exception\Validation
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

        if (empty($properties)) {
            return;
        }

        foreach ($properties as $key => $value) {
            if (!preg_match('#@column#s', $value->getDocComment())) {
                continue;
            }

            if (stripos($value->class, 'basic') !== false) {
                $propertyName = $value->getName();
                $getProperty = 'get' . ucfirst(str_replace('_', '', $value->getName()));

                if (trim((string)$original->$getProperty()) !== trim((string)$edited->$getProperty())) {
                    $changes[$propertyName] = $original->$getProperty();
                }
            }
        }

        if (\count($changes)) {
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
                Event::fire('app.log',
                    ['fail', 'Advertisement history errors: ' . json_encode($historyRecord->getErrors())]);
            }
        }
    }

}
