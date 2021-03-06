<?php

namespace App\Model;

use App\Model\Basic\BasicPartnerModel;

/**
 * 
 */
class PartnerModel extends BasicPartnerModel
{

    /**
     * @readwrite
     */
    protected $_alias = 'pa';

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
     * @return mixed
     * @throws \THCFrame\Model\Exception\Connector
     * @throws \THCFrame\Model\Exception\Implementation
     */
    public function delete()
    {
        $imgMain = $this->getUnlinkLogoPath();

        $state = parent::delete();

        if ($state != -1) {
            @unlink($imgMain);
        }

        return $state;
    }

    /**
     * @param bool $type
     * @return string
     */
    public function getUnlinkLogoPath($type = true)
    {
        if ($type && !empty($this->_logo)) {
            if (file_exists('./' . $this->_logo)) {
                return './' . $this->_logo;
            } elseif (file_exists('.' . $this->_logo)) {
                return '.' . $this->_logo;
            } elseif (file_exists($this->_logo)) {
                return $this->_logo;
            }
        }

        return $this->_logo;
    }

}
