<?php

namespace Search\Model\Basic;

use THCFrame\Model\Model;

/**
 *
 */
class BasicSearchIndexModel extends Model
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
     * @type int
     * @length 11
     * @validate numeric,max(11)
     * @label id zdroje
     * @unsigned
     */
    protected $_sourceId;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 100
     *
     * @validate required, alphanumeric, max(100)
     * @label word
     */
    protected $_sword;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 350
     *
     * @validate path, max(350)
     * @label path to source
     */
    protected $_pathToSource;

    /**
     * @column
     * @readwrite
     * @type varchar
     * @length 150
     *
     * @validate path, max(150)
     * @label source title
     */
    protected $_sourceTitle;

    /**
     * @column
     * @readwrite
     * @type text
     * @null
     *
     * @validate alphanumeric
     * @label source meta description
     */
    protected $_sourceMetaDescription;

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
    protected $_sourceCreated;

    /**
     * @column
     * @readwrite
     * @type smallint
     * @unsigned
     *
     * @validate numeric, max(5)
     * @label occurence
     */
    protected $_occurence;

    /**
     * @column
     * @readwrite
     * @type smallint
     * @unsigned
     *
     * @validate numeric, max(5)
     * @label weight
     */
    protected $_weight;

    /**
     * @column
     * @readwrite
     * @type text
     * @null
     *
     * @validate json
     * @label additionalData
     */
    protected $_additionalData;

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

    public function getSourceId()
    {
        return $this->_sourceId;
    }

    public function getSword()
    {
        return $this->_sword;
    }

    public function getPathToSource()
    {
        return $this->_pathToSource;
    }

    public function getSourceTitle()
    {
        return $this->_sourceTitle;
    }

    public function getSourceMetaDescription()
    {
        return $this->_sourceMetaDescription;
    }

    public function getSourceCreated()
    {
        return $this->_sourceCreated;
    }

    public function getOccurence()
    {
        return $this->_occurence;
    }

    public function getWeight()
    {
        return $this->_weight;
    }

    public function getAdditionalData()
    {
        return $this->_additionalData;
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

    public function setSourceId($sourceId)
    {
        $this->_sourceId = $sourceId;
        return $this;
    }

    public function setSword($sword)
    {
        $this->_sword = $sword;
        return $this;
    }

    public function setPathToSource($pathToSource)
    {
        $this->_pathToSource = $pathToSource;
        return $this;
    }

    public function setSourceTitle($sourceTitle)
    {
        $this->_sourceTitle = $sourceTitle;
        return $this;
    }

    public function setSourceMetaDescription($sourceMetaDescription)
    {
        $this->_sourceMetaDescription = $sourceMetaDescription;
        return $this;
    }

    public function setSourceCreated($sourceCreated)
    {
        $this->_sourceCreated = $sourceCreated;
        return $this;
    }

    public function setOccurence($occurence)
    {
        $this->_occurence = $occurence;
        return $this;
    }

    public function setWeight($weight)
    {
        $this->_weight = $weight;
        return $this;
    }

    public function setAdditionalData($additionalData)
    {
        $this->_additionalData = $additionalData;
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
