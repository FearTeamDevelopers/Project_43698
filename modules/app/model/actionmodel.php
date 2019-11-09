<?php

namespace App\Model;

use App\Model\Basic\BasicActionModel;
use THCFrame\Core\StringMethods;
use THCFrame\Core\Lang;
use THCFrame\Registry\Registry;
use Search\Model\IndexableInterface;

class ActionModel extends BasicActionModel implements IndexableInterface
{

    public const STATE_WAITING = 0;
    public const STATE_APPROVED = 1;
    public const STATE_REJECTED = 2;

    /**
     * @var array
     */
    private static $_statesConv = [
        self::STATE_WAITING => 'Čeká na shválení',
        self::STATE_APPROVED => 'Schváleno',
        self::STATE_REJECTED => 'Zamítnuto',
    ];

    /**
     * @readwrite
     */
    protected $_alias = 'ac';

    /**
     * Check whether action unique identifier already exist or not.
     *
     * @param string $key
     * @return bool
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    private static function checkUrlKey($key)
    {
        $status = self::first(['urlKey = ?' => $key]);

        return null === $status;
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
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAll(): ?array
    {
        $query = self::getQuery(['ac.*'])
                ->join('tb_user', 'ac.userId = us.id', 'us', ['us.firstname', 'us.lastname']);

        return self::initialize($query);
    }

    /**
     * @param int $limit
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchWithLimit($limit = 10): ?array
    {
        $query = self::getQuery(['ac.*'])
                ->join('tb_user', 'ac.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->order('ac.created', 'desc')
                ->limit((int) $limit);

        return self::initialize($query);
    }

    /**
     * @param int $limit
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchActiveWithLimit($limit = 10, $page = 1): ?array
    {
        if ($limit === 0) {
            $actions = self::all(['active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false, 'startDate >= ?' => date('Y-m-d')],
                    ['id', 'urlKey', 'userAlias', 'title', 'shortBody', 'created', 'startDate'],
                    ['rank' => 'desc', 'startDate' => 'asc']
            );
        } else {
            $actions = self::all(['active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false, 'startDate >= ?' => date('Y-m-d')],
                    ['id', 'urlKey', 'userAlias', 'title', 'shortBody', 'created', 'startDate'],
                    ['rank' => 'desc', 'startDate' => 'asc'], $limit, $page
            );
        }

        return $actions;
    }

    /**
     * @param $lastId
     * @param $lastStartDate
     * @return array
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchMoreActionsToHomepage($lastId, $lastStartDate): array
    {
        if(empty($lastId) || empty($lastStartDate)){
            return [];
        }

        $starDate = date('Y-m-d', strtotime($lastStartDate));

        $actions = self::all(
                ['id <> ?' => $lastId, 'active = ?' => true, 'approved = ?' => 1,
                    'archive = ?' => false, 'startDate >= ?' => $starDate],
                ['id', 'urlKey', 'userAlias', 'title', 'shortBody', 'created', 'startDate',
                    '(select id from tb_action where active = 1 and approved = 1 and archive = 0 order by startDate desc limit 1) as maxId'],
                ['rank' => 'desc', 'startDate' => 'asc'], 9
        );
        $returnArr = [];

        if (null !== $actions) {
            foreach ($actions as $action) {
                $returnArr[] = [
                    'id' => $action->getId(),
                    'maxId' => $action->getMaxId(),
                    'title' => $action->getTitle(),
                    'urlKey' => $action->getUrlKey(),
                    'startDate' => $action->getStartDate(),
                    'day' => \App\Helper\DateFormater::g2dn($action->getStartDate()),
                    'month' => \App\Helper\DateFormater::g2mn($action->getStartDate())
                ];
            }
        }

        return $returnArr;
    }

    /**
     * @param int $limit
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchOldWithLimit($limit = 10, $page = 1): ?array
    {
        $actions = self::all(['active = ?' => true, 'approved = ?' => 1, 'archive = ?' => false, 'startDate <= ?' => date('Y-m-d')],
                ['urlKey', 'userAlias', 'title', 'shortBody', 'created', 'startDate'],
                ['rank' => 'desc', 'startDate' => 'desc'], $limit, $page
        );

        return $actions;
    }

    /**
     * @param $year
     * @param int $page
     * @param int $limit
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchArchivatedWithLimit($year, $page = 1, $limit = 10): ?array
    {
        $actions = self::all(
                ['active = ?' => true, 'approved = ?' => 1, 'startDate < ?' => date('Y-m-d'), 'startDate >= ?' => $year . '-01-01', 'endDate <= ?' => $year . '-12-31'],
                ['urlKey', 'userAlias', 'title', 'shortBody', 'created', 'rank', 'startDate'],
                ['rank' => 'desc', 'startDate' => 'desc'], $limit, $page
        );

        return $actions;
    }

    /**
     * @param $urlKey
     * @return mixed
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchByUrlKey($urlKey)
    {
        return self::first(['active = ?' => true, 'approved' => 1, 'urlKey = ?' => $urlKey]);
    }

    /**
     * @return array
     */
    public static function getStates(): array
    {
        return self::$_statesConv;
    }

    /**
     * @param \THCFrame\Bag\BagInterface $post
     * @param array $options
     * @return array
     * @throws \THCFrame\Core\Exception\Argument
     * @throws \THCFrame\Core\Exception\Lang
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function createFromPost(\THCFrame\Bag\BagInterface $post, array $options = []): array
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
            }

            $urlKeyCh = $urlKey . '-' . $i;

            if ($i == 100) {
                $errors['title'] = [Lang::get('ARTICLE_UNIQUE_ID')];
                break;
            }
        }

        if ($post->get('datestart') > $post->get('dateend')) {
            $errors['startDate'] = [Lang::get('ARTICLE_STARTDATE_ERROR')];
        }

        $shortText = str_replace(['(!read_more_link!)', '(!read_more_title!)'],
                ['/akce/r/' . $urlKey, '[Celý článek]'], $post->get('shorttext'));

        $keywords = strtolower(StringMethods::removeDiacriticalMarks($post->get('keywords')));
        $metaDesc = StringMethods::removeMultipleSpaces(strip_tags($post->get('metadescription', $shortText)));

        $action = new static([
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
            'startTime' => $post->get('timestart', '0:00'),
            'endTime' => $post->get('timeend', '0:00'),
            'keywords' => $keywords,
            'metaTitle' => $post->get('metatitle', $post->get('title')),
            'metaDescription' => $metaDesc,
            'created' => date('Y-m-d H:i'),
            'modified' => date('Y-m-d H:i'),
        ]);

        return [$action, $errors];
    }

    /**
     * @param \THCFrame\Bag\BagInterface $post
     * @param ActionModel $action
     * @param array $options
     * @return array
     * @throws \THCFrame\Core\Exception\Argument
     * @throws \THCFrame\Core\Exception\Lang
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function editFromPost(\THCFrame\Bag\BagInterface $post, ActionModel $action, array $options = [])
    {
        $urlKey = $urlKeyCh = StringMethods::createUrlKey($post->get('title'));
        $errors = [];
        $user = $options['user'];

        if (empty($user)) {
            throw new \THCFrame\Core\Exception\Argument('Not all of required options are available');
        }

        if ($action->urlKey != $urlKey && !self::checkUrlKey($urlKey)) {
            for ($i = 1; $i <= 100; $i+=1) {
                if (self::checkUrlKey($urlKeyCh)) {
                    break;
                }

                $urlKeyCh = $urlKey . '-' . $i;

                if ($i == 100) {
                    $errors['title'] = [Lang::get('ARTICLE_TITLE_IS_USED')];
                    break;
                }
            }
        }

        if (null === $action->userId) {
            $action->userId = $user->getId();
            $action->userAlias = $user->getWholeName();
        }

        $shortText = str_replace(
                ['(!read_more_link!)', '(!read_more_title!)'], ['/akce/r/' . $urlKey, '[Celý článek]'], $post->get('shorttext')
        );

        if ($post->get('datestart') > $post->get('dateend')) {
            $errors['startDate'] = [Lang::get('ARTICLE_STARTDATE_ERROR')];
        }

        $keywords = strtolower(StringMethods::removeDiacriticalMarks($post->get('keywords')));
        $metaDesc = StringMethods::removeMultipleSpaces(strip_tags($post->get('metadescription', $shortText)));

        if ($options['isAdmin']) {
            $action->approved = $post->get('approve');
        } else {
            $action->approved = $options['autoPublish'];
        }

        $action->title = $post->get('title');
        $action->urlKey = $urlKeyCh;
        $action->body = $post->get('text');
        $action->shortBody = $shortText;
        $action->rank = $post->get('rank', 1);
        $action->startDate = $post->get('datestart');
        $action->endDate = $post->get('dateend');
        $action->startTime = $post->get('timestart', '0:00');
        $action->endTime = $post->get('timeend', '0:00');
        $action->active = $post->get('active');
        $action->archive = $post->get('archive');
        $action->keywords = $keywords;
        $action->metaTitle = $post->get('metatitle', $post->get('title'));
        $action->metaDescription = $metaDesc;

        return [$action, $errors];
    }

    /**
     * @return array
     */
    public static function fetchActionYears(): array
    {
        $db = Registry::get('database')->get('main');
        $result = $db->execute('SELECT DISTINCT YEAR(startDate) as a_years '
                . 'FROM tb_action '
                . "WHERE (startDate is not null or startDate != '') and YEAR(startDate) <= YEAR(curdate()) "
                . 'ORDER BY 1 DESC');

        $returnArr = [];
        if (!empty($result)) {
            foreach ($result as $row) {
                if (!empty($row['a_years'])) {
                    $returnArr[] = $row['a_years'];
                }
            }
        }

        unset($result);
        return $returnArr;
    }

}
