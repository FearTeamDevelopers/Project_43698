<?php

namespace App\Model;

use App\Model\Basic\BasicNewsModel;
use THCFrame\Core\StringMethods;
use THCFrame\Core\Lang;
use Search\Model\IndexableInterface;

/**
 *
 */
class NewsModel extends BasicNewsModel implements IndexableInterface
{

    const STATE_WAITING = 0;
    const STATE_APPROVED = 1;
    const STATE_REJECTED = 2;

    /**
     * @var type
     */
    private static $_statesConv = [
        self::STATE_WAITING => 'Čeká na shválení',
        self::STATE_APPROVED => 'Schváleno',
        self::STATE_REJECTED => 'Zamítnuto',
    ];

    /**
     * @readwrite
     */
    protected $_alias = 'nw';

    /**
     * @readwrite
     */
    protected $_fbLikeUrl;

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
        $query = self::getQuery(['nw.*'])
                ->join('tb_user', 'nw.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

        return self::initialize($query);
    }

    /**
     * Called from admin module.
     *
     * @return array
     */
    public static function fetchWithLimit($limit = 10, $page = 1)
    {
        $query = self::getQuery(['nw.*'])
                ->join('tb_user', 'nw.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->order('nw.created', 'desc')
                ->limit((int) $limit, $page);

        return self::initialize($query);
    }

    /**
     * Called from app module.
     *
     * @param type $limit
     *
     * @return type
     */
    public static function fetchActiveWithLimit($limit = 10, $page = 1)
    {
        $news = self::all(['active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false],
                ['urlKey', 'userAlias', 'title', 'shortBody', 'created'],
                ['rank' => 'desc', 'created' => 'desc'], $limit, $page
        );

        return $news;
    }

    /**
     * Called from app module.
     *
     * @param type $limit
     *
     * @return type
     */
    public static function fetchArchivatedWithLimit($limit = 10, $page = 1)
    {
        $news = self::all(['active = ?' => true, 'approved = ?' => 1, 'archive = ?' => true],
                ['urlKey', 'userAlias', 'title', 'shortBody', 'created'],
                ['rank' => 'desc', 'created' => 'desc'], $limit, $page
        );

        return $news;
    }

    /**
     * Called from app module.
     *
     * @param type $urlKey
     *
     * @return type
     */
    public static function fetchByUrlKey($urlKey)
    {
        return self::first(['active = ?' => true, 'approved' => 1, 'urlKey = ?' => $urlKey]);
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
     * Check whether unique identifier already exist or not
     *
     * @param type $urlKey
     * @return boolean
     */
    public static function checkUrlKey($urlKey)
    {
        $status = self::first(['urlKey = ?' => $urlKey]);

        if (null === $status) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param \THCFrame\Bag\BagInterface $post
     * @param array $options
     * @return array
     */
    public static function createFromPost(\THCFrame\Bag\BagInterface $post, array $options = [])
    {
        $urlKey = $urlKeyCh = StringMethods::createUrlKey($post->get('title'));
        $errors = [];
        $user = $options['user'];

        if (empty($user)) {
            throw new \THCFrame\Core\Exception\Argument('Not all of required options are available');
        }

        for ($i = 1; $i <= 100; $i+=1) {
            if (self::checkUrlKey($urlKeyCh)) {
                break;
            } else {
                $urlKeyCh = $urlKey . '-' . $i;
            }

            if ($i == 100) {
                $errors['title'] = [Lang::get('ARTICLE_UNIQUE_ID')];
                break;
            }
        }

        $shortText = str_replace(['(!read_more_link!)', '(!read_more_title!)'],
                ['/novinky/r/' . $urlKey, '[Celý článek]'], $post->get('shorttext'));

        $keywords = strtolower(StringMethods::removeDiacriticalMarks($post->get('keywords')));
        $metaDesc = StringMethods::removeMultipleSpaces(strip_tags($post->get('metadescription', $shortText)));

        $news = new static([
            'title' => $post->get('title'),
            'userId' => $user->getId(),
            'userAlias' => $user->getWholeName(),
            'urlKey' => $urlKeyCh,
            'approved' => $options['autoPublish'],
            'archive' => 0,
            'shortBody' => $shortText,
            'body' => $post->get('text'),
            'rank' => $post->get('rank', 1),
            'keywords' => $keywords,
            'metaTitle' => $post->get('metatitle', $post->get('title')),
            'metaDescription' => $metaDesc,
        ]);

        return [$news, $errors];
    }

    /**
     *
     * @param \THCFrame\Bag\BagInterface $post
     * @param \App\Model\NewsModel $news
     * @param array $options
     * @return array
     */
    public static function editFromPost(\THCFrame\Bag\BagInterface $post, NewsModel $news, array $options = [])
    {
        $urlKey = $urlKeyCh = StringMethods::createUrlKey($post->get('title'));
        $errors = [];
        $user = $options['user'];

        if (empty($user)) {
            throw new \THCFrame\Core\Exception\Argument('Not all of required options are available');
        }


        if ($news->urlKey != $urlKey && !self::checkUrlKey($urlKey)) {
            for ($i = 1; $i <= 100; $i+=1) {
                if (self::checkUrlKey($urlKeyCh)) {
                    break;
                } else {
                    $urlKeyCh = $urlKey . '-' . $i;
                }

                if ($i == 100) {
                    $errors['title'] = [Lang::get('ARTICLE_TITLE_IS_USED')];
                    break;
                }
            }
        }

        if (null === $news->userId) {
            $news->userId = $user->getId();
            $news->userAlias = $user->getWholeName();
        }

        $shortText = str_replace(['(!read_more_link!)', '(!read_more_title!)'],
                ['/novinky/r/' . $urlKey, '[Celý článek]'], $post->get('shorttext'));

        $keywords = strtolower(StringMethods::removeDiacriticalMarks($post->get('keywords')));
        $metaDesc = StringMethods::removeMultipleSpaces(strip_tags($post->get('metadescription', $shortText)));

        if ($options['isAdmin']) {
            $news->approved = $post->get('approve');
        } else {
            $news->approved = $options['autoPublish'];
        }

        $news->title = $post->get('title');
        $news->urlKey = $urlKeyCh;
        $news->body = $post->get('text');
        $news->shortBody = $shortText;
        $news->rank = $post->get('rank', 1);
        $news->active = $post->get('active');
        $news->archive = $post->get('archive');
        $news->keywords = $keywords;
        $news->metaTitle = $post->get('metatitle', $post->get('title'));
        $news->metaDescription = $metaDesc;

        return [$news, $errors];
    }

}
