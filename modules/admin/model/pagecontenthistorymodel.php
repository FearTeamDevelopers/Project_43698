<?php

namespace Admin\Model;

use App\Model\PageContentModel;
use ReflectionClass;
use ReflectionException;
use THCFrame\Model\Exception\Validation;
use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;
use Admin\Model\Basic\BasicPagecontenthistoryModel;

/**
 * 
 */
class PageContentHistoryModel extends BasicPagecontenthistoryModel
{
    /**
     * @readwrite
     */
    protected $_alias = 'pch';

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
        $query = self::getQuery(['pch.*'])
                ->join('tb_user', 'pch.editedBy = us.id', 'us',
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
        $query = self::getQuery(['pch.*'])
                ->join('tb_user', 'pch.editedBy = us.id', 'us',
                        ['us.firstname', 'us.lastname'])
                ->order('pch.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * Check differences between two objects.
     *
     * @param PageContentModel $original
     * @param PageContentModel $edited
     * @throws ReflectionException
     * @throws Validation
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function logChanges(PageContentModel $original, PageContentModel $edited): void
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
            Event::fire('admin.log', ['success', 'PageContent '.$original->getId().' changes saved']);
        } else {
            Event::fire('admin.log', ['fail', 'PageContent history errors: '.json_encode($historyRecord->getErrors())]);
        }
    }
}
