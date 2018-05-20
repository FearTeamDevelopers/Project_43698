<?php

namespace Search\Model\Basic;

use THCFrame\Model\Model;

/**
 *
 */
class BasicSearchIndexLogModel extends Model
{

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     * @unsigned
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 100
     *
     * @validate alpha, max(100)
     * @label source model
     */
    protected $_sourceModel;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 100
     *
     * @validate alpha, max(100)
     * @label table
     */
    protected $_idxTableAlias;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 100
     *
     * @validate alphanumeric, max(100)
     * @label run by
     */
    protected $_runBy;

    /**
     * @column
     * @readwrite
     * @index
     * @type tinyint
     * @length 1
     *
     * @default 0
     * @validate max(1)
     */
    protected $_isManualIndex;

    /**
     * @column
     * @readwrite
     * @type smallint
     * @unsigned
     *
     * @validate numeric, max(8)
     * @label words count
     */
    protected $_wordsCount;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @null
     *
     * @default null
     * @validate datetime, max(19)
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type char
     * @length 19
     * @null
     *
     * @default null
     * @validate datetime, max(19)
     */
    protected $_modified;

    public function getId()
    {
        return $this->_id;
    }

    public function getSourceModel()
    {
        return $this->_sourceModel;
    }

    public function getIdxTableAlias()
    {
        return $this->_idxTableAlias;
    }

    public function getRunBy()
    {
        return $this->_runBy;
    }

    public function getIsManualIndex()
    {
        return $this->_isManualIndex;
    }

    public function getWordsCount()
    {
        return $this->_wordsCount;
    }

    public function getCreated()
    {
        return $this->_created;
    }

    public function getModified()
    {
        return $this->_modified;
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    public function setSourceModel($sourceModel)
    {
        $this->_sourceModel = $sourceModel;
        return $this;
    }

    public function setIdxTableAlias($idxTableAlias)
    {
        $this->_idxTableAlias = $idxTableAlias;
        return $this;
    }

    public function setRunBy($runBy)
    {
        $this->_runBy = $runBy;
        return $this;
    }

    public function setIsManualIndex($isManualIndex)
    {
        $this->_isManualIndex = $isManualIndex;
        return $this;
    }

    public function setWordsCount($wordsCount)
    {
        $this->_wordsCount = $wordsCount;
        return $this;
    }

    public function setCreated($created)
    {
        $this->_created = $created;
        return $this;
    }

    public function setModified($modified)
    {
        $this->_modified = $modified;
        return $this;
    }

}
