<?php

namespace App\Model;

use App\Model\Basic\BasicAdmessageModel;

/**
 * 
 */
class AdMessageModel extends BasicAdmessageModel
{

    /**
     * @readwrite
     */
    protected $_alias = 'adm';

    /**
     * 
     */
    public function preSave()
    {
        $primary = $this->getPrimaryColumn();
        $raw = $primary['raw'];

        if (empty($this->$raw)) {
            $this->setCreated(date('Y-m-d H:i:s'));
            $this->setMessageSent(false);
        }
        $this->setModified(date('Y-m-d H:i:s'));
    }

}
