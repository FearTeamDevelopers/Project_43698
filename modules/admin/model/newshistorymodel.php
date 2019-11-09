<?php

namespace Admin\Model;

use App\Model\NewsModel;
use ReflectionClass;
use ReflectionException;
use THCFrame\Model\Exception\Validation;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;
use Admin\Model\Basic\BasicNewshistoryModel;

/**
 * 
 */
class NewsHistoryModel extends BasicNewshistoryModel
{
    /**
     * @readwrite
     */
    protected $_alias = 'nwh';

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
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAll(): ?array
    {
        $query = self::getQuery(['nwh.*'])
                ->join('tb_user', 'nwh.editedBy = us.id', 'us',
                        ['us.firstname', 'us.lastname']);

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     *
     * @param int $limit
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchWithLimit($limit = 10, $page = 1): ?array
    {
        $query = self::getQuery(['nwh.*'])
                ->join('tb_user', 'nwh.editedBy = us.id', 'us',
                        ['us.firstname', 'us.lastname'])
                ->order('nwh.created', 'desc')
                ->limit((int) $limit, $page);

        return self::initialize($query);
    }

    /**
     * Check differences between two objects.
     *
     * @param NewsModel $original
     * @param NewsModel $edited
     * @throws ReflectionException
     * @throws Validation
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function logChanges(NewsModel $original, NewsModel $edited): void
    {
        $sec = Registry::get('security');
        $user = $sec->getUser();

        $remoteAddr = RequestMethods::getClientIpAddress();
        $referer = RequestMethods::server('HTTP_REFERER');
        $changes = [];

        $reflect = new ReflectionClass($original);
        $properties = $reflect->getProperties();
        $className = get_class($original);

        if (empty($properties)) {
            return;
        }

        foreach ($properties as $key => $value) {
            if(!preg_match('#.*@column.*#s', $value->getDocComment())){
                continue;
            }
            if ($value->class == $className) {
                $propertyName = $value->getName();
                $getProperty = 'get'.ucfirst(str_replace('_', '', $value->getName()));

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
            Event::fire('admin.log', ['success', 'News '.$original->getId().' changes saved']);
        } else {
            Event::fire('admin.log', ['fail', 'News history errors: '.json_encode($historyRecord->getErrors())]);
        }
    }
}
