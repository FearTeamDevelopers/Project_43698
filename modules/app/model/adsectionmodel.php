<?php

namespace App\Model;

use App\Model\Basic\BasicAdsectionModel;

/**
 *
 */
class AdSectionModel extends BasicAdsectionModel
{

    /**
     * @readwrite
     */
    protected $_alias = 'ads';

    /**
     * @readwrite
     *
     * @var type
     */
    protected $_adTenderCount;

    /**
     * @readwrite
     *
     * @var type
     */
    protected $_adDemandCount;

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
    public static function fetchAll()
    {
        $sections = self::all(
                        [], ['ads.*',
                    '(SELECT COUNT(adv.id) FROM `tb_advertisement` adv WHERE adv.sectionId = ads.id AND adv.adtype="tender")' => 'adTenderCount',
                    '(SELECT COUNT(adv.id) FROM `tb_advertisement` adv WHERE adv.sectionId = ads.id AND adv.adtype="demand")' => 'adDemandCount',]
        );

        return $sections;
    }

    /**
     * @return array|null
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function fetchAllActive()
    {
        $sections = self::all(
                        ['ads.active = ?' => true], ['ads.*',
                    '(SELECT COUNT(adv.id) FROM `tb_advertisement` adv WHERE adv.sectionId = ads.id AND adv.adtype="tender" AND adv.active=1)' => 'adTenderCount',
                    '(SELECT COUNT(adv.id) FROM `tb_advertisement` adv WHERE adv.sectionId = ads.id AND adv.adtype="demand" AND adv.active=1)' => 'adDemandCount',
                        ]
        );

        return $sections;
    }

    /**
     * Check whether unique ad section identifier already exist or not.
     *
     * @param string $key
     *
     * @return bool
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public static function checkUrlKey($key)
    {
        $status = self::first(['urlKey = ?' => $key]);

        return null === $status;
    }
}
