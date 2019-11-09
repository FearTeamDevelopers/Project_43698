<?php

namespace Admin\Model;

use Admin\Model\Basic\BasicImessageModel;

/**
 * 
 */
class ImessageModel extends BasicImessageModel
{
    public const TYPE_INFO = 1;
    public const TYPE_WARNING = 2;
    public const TYPE_ERROR = 3;

    /**
     * @var array
     */
    private static $_typesConv = [
        self::TYPE_INFO => 'Info',
        self::TYPE_WARNING => 'Warning',
        self::TYPE_ERROR => 'Error',
    ];

    /**
     * @readwrite
     */
    protected $_alias = 'ims';

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
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAll(): ?array
    {
        $query = self::getQuery(['ims.*'])
                ->join('tb_user', 'ims.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

        return self::initialize($query);
    }

    /**
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchActive(): ?array
    {
        return self::all(['displayFrom <= ?' => date('Y-m-d'), 'displayTo >= ?' => date('Y-m-d'), 'active = ?' => true]);
    }

    /**
     * Get imessage types.
     * 
     * @return array
     */
    public static function getTypes(): array
    {
        return self::$_typesConv;
    }
}
