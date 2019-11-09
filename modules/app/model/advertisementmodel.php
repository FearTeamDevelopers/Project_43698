<?php

namespace App\Model;

use App\Model\Basic\BasicAdvertisementModel;
use Search\Model\IndexableInterface;

/**
 *
 */
class AdvertisementModel extends BasicAdvertisementModel implements IndexableInterface
{

    public const STATE_SOLD = 2;

    /**
     * @readwrite
     */
    protected $_alias = 'adv';

    /**
     * @readwrite
     */
    protected $_messageCount;

    /**
     * @readwrite
     */
    protected $_messages;

    /**
     * @readwrite
     */
    protected $_images;

    public function getBody()
    {
        return '';
    }

    public function getMetaDescription()
    {
        return $this->getContent();
    }

    public function getUrlKey()
    {
        return $this->getUniqueKey();
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
            $this->setHasAvailabilityRequest(false);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

    /**
     * Check whether ad unique identifier already exist or not.
     *
     * @param string $str
     *
     * @return bool
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function checkAdKey($str)
    {
        $ad = self::first(['uniqueKey = ?' => $str]);

        return $ad === null;
    }

    /**
     * Called from admin module.
     *
     * @return array
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAll()
    {
        $query = self::getQuery(['adv.id', 'adv.title', 'adv.adType', 'adv.expirationDate',
                    'adv.active', 'adv.created', 'adv.hasAvailabilityRequest', 'adv.userId', 'adv.state',
                    '(SELECT COUNT(adm.id) FROM tb_admessage adm where adm.adId = adv.id)' => 'messageCount',])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle']);

        return self::initialize($query);
    }

    /**
     * @param $id
     * @return mixed|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchById($id)
    {
        $query = self::getQuery(['adv.*'])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                ->where('adv.id = ?', (int) $id);

        $adArr = self::initialize($query);
        $ad = !empty($adArr) ? array_shift($adArr) : null;

        if (null !== $ad) {
            $ad->messages = \App\Model\AdMessageModel::all(['adId = ?' => $ad->getId()]);
            $ad->images = \App\Model\AdImageModel::all(['adId = ?' => $ad->getId()]);
        }

        return $ad;
    }

    /**
     * @param int $adsPerPage
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAdsActive($adsPerPage = 10, $page = 1)
    {
        $query = self::getQuery(['adv.id', 'adv.uniqueKey', 'adv.userAlias',
                    'adv.title', 'adv.price', 'adv.userId', 'adv.state', 'adv.content'])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                ->leftjoin('tb_adimage', 'adi.id = adv.mainPhotoId', 'adi', ['adi.photoName', 'adi.imgMain', 'adi.imgThumb'])
                ->where('adv.active = ?', true)
                ->where('adv.state <> ?', self::STATE_SOLD)
                ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                ->order('adv.created', 'desc')
                ->limit((int) $adsPerPage, (int) $page);

        return self::initialize($query);
    }

    /**
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAdsActiveNoLimit()
    {
        $query = self::getQuery(['adv.id', 'adv.uniqueKey', 'adv.adType', 'adv.userAlias',
                    'adv.title', 'adv.price', 'adv.created', 'adv.userId', 'adv.state', 'adv.mainPhotoId',
                    'adv.content', 'adv.keywords', 'adv.expirationDate'])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                ->leftjoin('tb_adimage', 'adi.id = adv.mainPhotoId', 'adi', ['adi.photoName', 'adi.imgMain', 'adi.imgThumb'])
                ->where('adv.active = ?', true)
                ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'));

        return self::initialize($query);
    }

    /**
     * @param $type
     * @param int $adsPerPage
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchActiveByType($type, $adsPerPage = 10, $page = 1)
    {
        if ($type == 'tender' || $type == 'demand') {
            $query = self::getQuery(['adv.id', 'adv.uniqueKey', 'adv.adType', 'adv.userAlias',
                        'adv.title', 'adv.price', 'adv.created', 'adv.userId', 'adv.state'])
                    ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                    ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                    ->leftjoin('tb_adimage', 'adi.id = adv.mainPhotoId', 'adi', ['adi.photoName', 'adi.imgMain', 'adi.imgThumb'])
                    ->where('adv.active = ?', true)
                    ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                    ->where('adv.adType = ?', $type)
                    ->order('adv.created', 'desc')
                    ->limit((int) $adsPerPage, (int) $page);

            return self::initialize($query);
        } else {
            return null;
        }
    }

    /**
     * @param $type
     * @return int|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function countActiveByType($type)
    {
        if ($type == 'tender' || $type == 'demand') {
            return self::count(['active = ?' => true, 'expirationDate >= ?' => date('Y-m-d H:i:s'), 'adType = ?' => $type]);
        } else {
            return null;
        }
    }

    /**
     * @param $type
     * @param $section
     * @param int $adsPerPage
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchActiveByTypeSection($type, $section, $adsPerPage = 10, $page = 1)
    {
        if ($type == 'tender' || $type == 'demand') {
            $query = self::getQuery(['adv.id', 'adv.uniqueKey', 'adv.adType', 'adv.userAlias',
                        'adv.title', 'adv.price', 'adv.created', 'adv.userId', 'adv.state'])
                    ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                    ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                    ->leftjoin('tb_adimage', 'adi.id = adv.mainPhotoId', 'adi', ['adi.photoName', 'adi.imgMain', 'adi.imgThumb'])
                    ->where('ads.urlKey = ?', $section)
                    ->where('adv.active = ?', true)
                    ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                    ->where('adv.adType = ?', $type)
                    ->order('adv.created', 'desc')
                    ->limit((int) $adsPerPage, (int) $page);

            return self::initialize($query);
        }

        return null;
    }

    /**
     * @param $type
     * @param $section
     * @return int|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function countActiveByTypeSection($type, $section)
    {
        if ($type == 'tender' || $type == 'demand') {
            $query = self::getQuery(['COUNT(adv.id) as count'])
                    ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                    ->where('ads.urlKey = ?', $section)
                    ->where('adv.active = ?', true)
                    ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                    ->where('adv.adType = ?', $type);

            $arr = self::initialize($query);
            $obj = !empty($arr) ? array_shift($arr) : null;

            return (int) $obj->count;
        }

        return null;
    }

    /**
     * @param $section
     * @param int $adsPerPage
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchActiveBySection($section, $adsPerPage = 10, $page = 1)
    {
        $query = self::getQuery(['adv.id', 'adv.uniqueKey', 'adv.adType', 'adv.userAlias',
                    'adv.title', 'adv.price', 'adv.created', 'adv.userId', 'adv.state'])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                ->leftjoin('tb_adimage', 'adi.id = adv.mainPhotoId', 'adi', ['adi.photoName', 'adi.imgMain', 'adi.imgThumb'])
                ->where('ads.urlKey = ?', $section)
                ->where('adv.active = ?', true)
                ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                ->order('adv.created', 'desc')
                ->limit((int) $adsPerPage, (int) $page);

        return self::initialize($query);
    }

    /**
     * @param $section
     * @return int
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function countActiveBySection($section)
    {
        $query = self::getQuery(['COUNT(adv.id) as count'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                ->where('ads.urlKey = ?', $section)
                ->where('adv.active = ?', true)
                ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'));

        $arr = self::initialize($query);
        $obj = !empty($arr) ? array_shift($arr) : null;

        return (int) $obj->count;
    }

    /**
     * @param $uniquekey
     * @return mixed|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchActiveByKey($uniquekey)
    {
        $query = self::getQuery(['adv.*'])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname', 'us.email'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                ->where('adv.uniqueKey = ?', $uniquekey)
                ->where('adv.expirationDate >= ?', date('Y-m-d H:i:s'))
                ->where('adv.active = ?', true);

        $adArr = self::initialize($query);
        $ad = !empty($adArr) ? array_shift($adArr) : null;

        if (null !== $ad) {
            $ad->messages = \App\Model\AdMessageModel::all(['adId = ?' => $ad->getId()]);
            $ad->images = \App\Model\AdImageModel::all(['adId = ?' => $ad->getId()]);
        }

        return $ad;
    }

    /**
     * @param $userId
     * @param int $adsPerPage
     * @param int $page
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchActiveByUser($userId, $adsPerPage = 10, $page = 1)
    {
        $query = self::getQuery(['adv.id', 'adv.userId', 'adv.uniqueKey', 'adv.created',
                    'adv.title', 'adv.price', 'adv.content', 'adv.expirationDate', 'adv.state'])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                ->leftjoin('tb_adimage', 'adi.id = adv.mainPhotoId', 'adi', ['adi.photoName', 'adi.imgMain', 'adi.imgThumb'])
                ->where('adv.userId = ?', $userId)
                ->where('adv.active = ?', true)
                ->order('adv.created', 'desc')
                ->limit((int) $adsPerPage, (int) $page);

        return self::initialize($query);
    }

    /**
     * @param $userId
     * @return int
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function countActiveByUser($userId)
    {
        return self::count(['active = ?' => true, 'userId = ?' => (int) $userId]);
    }

    /**
     * @param $uniqueKey
     * @param $userId
     * @return mixed
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAdByKeyUserId($uniqueKey, $userId)
    {
        $ad = self::first(['uniqueKey = ?' => $uniqueKey, 'userId = ?' => $userId]);

        if (null !== $ad) {
            $ad->_images = \App\Model\AdImageModel::all(['adId = ?' => $ad->getId()]);
        }

        return $ad;
    }

    /**
     * Return advertisements that are going to expire in x days based on parameters
     * Advertisements returned in array are grouped by author email
     *
     * @param integer $max
     * @return array
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function expireInDays($max = 7)
    {
        $query = self::getQuery(['adv.id', 'adv.uniqueKey', 'adv.title', 'adv.userId', 'adv.state',
                    'adv.created', 'adv.expirationDate', 'datediff(expirationDate, curdate())' => 'expireIn'])
                ->join('tb_user', 'adv.userId = us.id', 'us', ['us.firstname', 'us.lastname', 'us.email'])
                ->join('tb_adsection', 'adv.sectionId = ads.id', 'ads', ['ads.title' => 'sectionTitle'])
                ->where('adv.active = ?', true)
                ->where('adv.hasAvailabilityRequest = ?', false)
                ->where('adv.state <> ?', 2)
                ->where('datediff(expirationDate, curdate()) = ?', (int) $max)
                ->order('adv.created', 'desc');

        $ads = self::initialize($query);
        $returnArr = [];

        if (!empty($ads)) {
            foreach ($ads as $ad) {
                $returnArr[$ad->email][] = [
                    'uniqueKey' => $ad->uniqueKey,
                    'expireIn' => $ad->expireIn,
                    'title' => $ad->title,
                    'created' => $ad->created];
            }
        }

        return $returnArr;
    }

}
