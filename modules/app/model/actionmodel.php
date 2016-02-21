<?php

namespace App\Model;

use App\Model\Basic\BasicActionModel;
use THCFrame\Core\StringMethods;
use THCFrame\Core\Lang;

/**
 *
 */
class ActionModel extends BasicActionModel
{

    const STATE_WAITING = 0;
    const STATE_APPROVED = 1;
    const STATE_REJECTED = 2;

    /**
     * @var type
     */
    private static $_statesConv = array(
        self::STATE_WAITING => 'Čeká na shválení',
        self::STATE_APPROVED => 'Schváleno',
        self::STATE_REJECTED => 'Zamítnuto',
    );

    /**
     * @readwrite
     */
    protected $_alias = 'ac';

    /**
     * Check whether action unique identifier already exist or not.
     *
     * @param string $key
     *
     * @return bool
     */
    private static function checkUrlKey($key)
    {
        $status = self::first(array('urlKey = ?' => $key));

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }

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

        $shortText = preg_replace('/https:/i', 'http:', $this->getShortBody());
        $text = preg_replace('/https:/i', 'http:', $this->getBody());
        $this->setShortBody($shortText);
        $this->setBody($text);
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * @return array
     */
    public static function fetchAll()
    {
        $query = self::getQuery(array('ac.*'))
                ->join('tb_user', 'ac.userId = us.id', 'us', array('us.firstname', 'us.lastname'));

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     *
     * @return array
     */
    public static function fetchWithLimit($limit = 10)
    {
        $query = self::getQuery(array('ac.*'))
                ->join('tb_user', 'ac.userId = us.id', 'us', array('us.firstname', 'us.lastname'))
                ->order('ac.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * Called from app module.
     *
     * @param type $limit
     * @param type $page
     * @return type
     */
    public static function fetchActiveWithLimit($limit = 10, $page = 1)
    {
        if ($limit === 0) {
            $actions = self::all(array('active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false, 'startDate >= ?' => date('Y-m-d', time())), array('id', 'urlKey', 'userAlias', 'title', 'shortBody', 'created', 'startDate'), array('rank' => 'desc', 'startDate' => 'asc')
            );
        } else {
            $actions = self::all(array('active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false, 'startDate >= ?' => date('Y-m-d', time())), array('id', 'urlKey', 'userAlias', 'title', 'shortBody', 'created', 'startDate'), array('rank' => 'desc', 'startDate' => 'asc'), $limit, $page
            );
        }

        return $actions;
    }

    /**
     * Called from app module.
     *
     * @param type $limit
     * @param type $page
     * @return type
     */
    public static function fetchOldWithLimit($limit = 10, $page = 1)
    {
        $actions = self::all(array('active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false, 'startDate <= ?' => date('Y-m-d', time())), array('urlKey', 'userAlias', 'title', 'shortBody', 'created', 'startDate'), array('rank' => 'desc', 'startDate' => 'desc'), $limit, $page
        );

        return $actions;
    }

    /**
     * Called from app module.
     *
     * @param type $limit
     * @param type $page
     * @return type
     */
    public static function fetchArchivatedWithLimit($limit = 10, $page = 1)
    {
        $actions = self::all(array('active = ?' => true, 'approved = ?' => 1, 'archive = ?' => true), array('urlKey', 'userAlias', 'title', 'shortBody', 'created', 'startDate'), array('rank' => 'desc', 'startDate' => 'desc'), $limit, $page
        );

        return $actions;
    }

    /**
     * Called from app module.
     *
     * @param type $urlKey
     * @return type
     */
    public static function fetchByUrlKey($urlKey)
    {
        return self::first(array('active = ?' => true, 'approved' => 1, 'urlKey = ?' => $urlKey));
    }

    /**
     * Return action states.
     *
     * @return array
     */
    public static function getStates()
    {
        return self::$_statesConv;
    }

    /**
     *
     * @param \THCFrame\Bag\BagInterface $post
     * @param array $options
     * @return type
     */
    public static function createFromPost(\THCFrame\Bag\BagInterface $post,
            array $options = array())
    {
        $urlKey = $urlKeyCh = StringMethods::createUrlKey($post->get('title'));
        $errors = array();
        $user = $options['user'];

        for ($i = 1; $i <= 100; $i+=1) {
            if (self::checkUrlKey($urlKeyCh)) {
                break;
            } else {
                $urlKeyCh = $urlKey . '-' . $i;
            }

            if ($i == 100) {
                $errors['title'] = array(Lang::get('ARTICLE_UNIQUE_ID'));
                break;
            }
        }

        if ($post->get('datestart') > $post->get('dateend')) {
            $errors['startDate'] = array(Lang::get('ARTICLE_STARTDATE_ERROR'));
        }

        if (!empty($post->get('timestart')) && empty($post->get('timeend'))) {
            $errors['startTime'] = array(Lang::get('ARTICLE_TIME_ERROR'));
        } elseif (!empty($post->get('timeend')) && empty($post->get('timestart'))) {
            $errors['startTime'] = array(Lang::get('ARTICLE_TIME_ERROR'));
        }

        $shortText = str_replace(array('(!read_more_link!)', '(!read_more_title!)'), array('/akce/r/' . $urlKey, '[Celý článek]'), $post->get('shorttext'));

        $keywords = strtolower(StringMethods::removeDiacriticalMarks($post->get('keywords')));

        $action = new static(array(
            'title' => $post->get('title'),
            'userId' => $user->getId(),
            'userAlias' => $user->getWholeName(),
            'urlKey' => $urlKeyCh,
            'approved' => $options['autoPublish'],
            'archive' => 0,
            'shortBody' => $shortText,
            'body' => $post->get('text'),
            'rank' => $post->get('rank', 1),
            'startDate' => $post->get('datestart'),
            'endDate' => $post->get('dateend'),
            'startTime' => $post->get('timestart'),
            'endTime' => $post->get('timeend'),
            'keywords' => $keywords,
            'metaTitle' => $post->get('metatitle', $post->get('title')),
            'metaDescription' => strip_tags($post->get('metadescription', $shortText)),
        ));

        return array($action, $errors);
    }

    /**
     *
     * @param \THCFrame\Bag\BagInterface $post
     * @param \App\Model\ActionModel $action
     * @param array $options
     * @return type
     */
    public static function editFromPost(\THCFrame\Bag\BagInterface $post,
            \App\Model\ActionModel $action, array $options = array())
    {
        $urlKey = $urlKeyCh = StringMethods::createUrlKey($post->get('title'));
        $errors = array();
        $user = $options['user'];

        if ($action->urlKey != $urlKey && !self::checkUrlKey($urlKey)) {
            for ($i = 1; $i <= 100; $i+=1) {
                if (self::checkUrlKey($urlKeyCh)) {
                    break;
                } else {
                    $urlKeyCh = $urlKey . '-' . $i;
                }

                if ($i == 100) {
                    $errors['title'] = array(Lang::get('ARTICLE_TITLE_IS_USED'));
                    break;
                }
            }
        }

        if (null === $action->userId) {
            $action->userId = $user->getId();
            $action->userAlias = $user->getWholeName();
        }

        $shortText = str_replace(
                array('(!read_more_link!)', '(!read_more_title!)'), array('/akce/r/' . $urlKey, '[Celý článek]'), $post->get('shorttext')
        );

        if ($post->get('datestart') > $post->get('dateend')) {
            $errors['startDate'] = array(Lang::get('ARTICLE_STARTDATE_ERROR'));
        }

        if (!empty($post->get('timestart')) && empty($post->get('timeend'))) {
            $errors['startTime'] = array(Lang::get('ARTICLE_TIME_ERROR'));
        } elseif (!empty($post->get('timeend')) && empty($post->get('timestart'))) {
            $errors['startTime'] = array(Lang::get('ARTICLE_TIME_ERROR'));
        }

        $keywords = strtolower(StringMethods::removeDiacriticalMarks($post->get('keywords')));

        if (!$this->isAdmin()) {
            $action->approved = $options['autoPublish'];
        } else {
            $action->approved = $post->get('approve');
        }

        $action->title = $post->get('title');
        $action->urlKey = $urlKeyCh;
        $action->body = $post->get('text');
        $action->shortBody = $shortText;
        $action->rank = $post->get('rank', 1);
        $action->startDate = $post->get('datestart');
        $action->endDate = $post->get('dateend');
        $action->startTime = $post->get('timestart');
        $action->endTime = $post->get('timeend');
        $action->active = $post->get('active');
        $action->archive = $post->get('archive');
        $action->keywords = $keywords;
        $action->metaTitle = $post->get('metatitle', $post->get('title'));
        $action->metaDescription = strip_tags($post->get('metadescription', $shortText));

        return array($action, $errors);
    }

}
