<?php

namespace Search\Model;

use Search\Model\Basic\BasicSearchIndexLogModel;

/**
 *
 */
class SearchIndexLogModel extends BasicSearchIndexLogModel
{
    /**
     * @readwrite
     */
    protected $_alias = 'sil';

    /**
     * @read
     */
    protected $_databaseIdent = 'search';

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
