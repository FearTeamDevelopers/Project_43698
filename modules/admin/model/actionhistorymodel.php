<?php

namespace Admin\Model;

use App\Model\ActionModel;
use ReflectionClass;
use ReflectionException;
use THCFrame\Model\Exception\Validation;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;
use Admin\Model\Basic\BasicActionhistoryModel;

/**
 *
 */
class ActionHistoryModel extends BasicActionhistoryModel
{
    /**
     * @readwrite
     */
    protected $_alias = 'ach';

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
        $query = self::getQuery(['ach.*'])
            ->join('tb_user', 'ach.editedBy = us.id', 'us',
                ['us.firstname', 'us.lastname']);

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     *
     * @param int $limit
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchWithLimit($limit = 10): ?array
    {
        $query = self::getQuery(['ach.*'])
            ->join('tb_user', 'ach.editedBy = us.id', 'us',
                ['us.firstname', 'us.lastname'])
            ->order('ach.created', 'desc')
            ->limit((int)$limit);

        return self::initialize($query);
    }

    /**
     * Check differences between two objects.
     *
     * @param ActionModel $original
     * @param ActionModel $edited
     * @throws ReflectionException
     * @throws Validation
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function logChanges(ActionModel $original, ActionModel $edited): void
    {
        $sec = Registry::get('security');
        $user = $sec->getUser();

        $remoteAddr = RequestMethods::getClientIpAddress();
        $referer = RequestMethods::server('HTTP_REFERER');
        $changes = [];

        $reflect = new ReflectionClass($original);
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
                'created' => date('Y-m-d H:i'),
            ]);

            if ($historyRecord->validate()) {
                $historyRecord->save();
                Event::fire('admin.log', ['success', 'Action ' . $original->getId() . ' changes saved']);
            } else {
                Event::fire('admin.log',
                    ['fail', 'Action history errors: ' . json_encode($historyRecord->getErrors())]);
            }
        }
    }
}
