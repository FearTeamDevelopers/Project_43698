<?php

namespace App\Model;

use App\Model\Basic\BasicFeedbackModel;

/**
 * 
 */
class FeedbackModel extends BasicFeedbackModel
{

    /**
     * @readwrite
     */
    protected $_alias = 'fb';

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
        $this->setModified(date('Y-m-d H:i:s'));
    }

}
