<?php

namespace App\Model;

use App\Model\Basic\BasicCommentModel;

/**
 *
 */
class CommentModel extends BasicCommentModel
{

    public const RESOURCE_ACTION = 1;
    public const RESOURCE_NEWS = 2;
    public const RESOURCE_REPORT = 3;

    private static $_resourceConv = [
        'action' => self::RESOURCE_ACTION,
        'news' => self::RESOURCE_NEWS,
        'report' => self::RESOURCE_REPORT,
    ];

    /**
     * @readwrite
     */
    protected $_alias = 'cm';

    /**
     * @readwrite
     *
     * @var array
     */
    public $_replies;

    /**
     *
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setDeleted(false);
            $this->setCreated(date('Y-m-d H:i:s'));
        }

        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * @return array
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAll()
    {
        $query = self::getQuery(['cm.*'])
                ->join('tb_user', 'cm.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

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
        $query = self::getQuery(['cm.*'])
                ->join('tb_user', 'cm.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->order('cm.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * @param $userId
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchByUserId($userId)
    {
        return self::all(['userId = ?' => (int) $userId], ['*'], ['created' => 'desc']);
    }

    /**
     * @param int $resourceId
     * @param int $type
     * @param int $limit
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchCommentsByResourceAndType($resourceId, $type, $limit = 20)
    {
        $query = self::getQuery(['cm.*'])
                ->join('tb_user', 'cm.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->where('cm.resourceId = ?', (int) $resourceId)
                ->where('cm.type = ?', (int) $type)
                ->where('cm.replyTo = ?', 0)
                ->order('cm.created', 'desc')
                ->limit((int) $limit);

        $comments = self::initialize($query);

        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $comment->_replies = self::fetchReplies($comment->getId());
            }
        }

        return $comments;
    }

    /**
     * @param $type
     * @param $resourceId
     * @param $created
     * @param int $limit
     * @return array|void|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchByTypeAndCreated($type, $resourceId, $created, $limit = 20)
    {
        $types = array_values(self::$_resourceConv);

        if (!in_array($type, $types)) {
            return;
        }

        $query = self::getQuery(['cm.*'])
                ->join('tb_user', 'cm.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->where('cm.resourceId = ?', (int) $resourceId)
                ->where('cm.created >= ?', (int) $created)
                ->where('cm.type = ?', (int) $type)
                ->order('cm.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * @param $id
     * @return array|null
     */
    public static function fetchReplies($id)
    {
        $comment = new self(['id' => $id]);

        $comment->_replies = $comment->getReplies();

        if ($comment->_replies !== null) {
            foreach ($comment->_replies as $cm) {
                $cm->_replies = self::fetchReplies($cm->getId());
            }
        }

        return $comment->_replies;
    }

    /**
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public function getReplies()
    {
        $query = self::getQuery(['cm.*'])
                ->join('tb_user', 'cm.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->where('cm.replyTo = ?', $this->getId())
                ->order('cm.created', 'desc');

        return self::initialize($query);
    }

}
