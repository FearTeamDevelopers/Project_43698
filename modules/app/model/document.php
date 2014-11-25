<?php

use THCFrame\Model\Model;

/**
 * Description of App_Model_Document
 *
 * @author Tomy
 */
class App_Model_Document extends Model
{

    /**
     * @readwrite
     */
    protected $_alias = 'dc';

    /**
     * @column
     * @readwrite
     * @primary
     * @type auto_increment
     */
    protected $_id;

    /**
     * @column
     * @readwrite
     * @type integer
     * @index
     * 
     * @validate numeric, max(8)
     */
    protected $_userId;

    /**
     * @column
     * @readwrite
     * @type boolean
     * @index
     * 
     * @validate max(3)
     */
    protected $_active;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 80
     * 
     * @validate alphanumeric, max(80)
     * @label alias autora
     */
    protected $_userAlias;
    
    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate alphanumeric, max(250)
     * @label popis
     */
    protected $_description;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 60
     * 
     * @validate alphanumeric, max(60)
     * @label název souboru
     */
    protected $_filename;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 250
     * 
     * @validate required, path, max(250)
     * @label cesta k souboru
     */
    protected $_filepath;

    /**
     * @column
     * @readwrite
     * @type tinyint
     * 
     * @validate numeric, max(2)
     * @label rank
     */
    protected $_rank;

    /**
     * @column
     * @readwrite
     * @type text
     * @length 10
     * 
     * @validate required, alpha, max(8)
     * @label format
     */
    protected $_format;

    /**
     * @column
     * @readwrite
     * @type integer
     * 
     * @validate required, numeric, max(8)
     * @label size
     */
    protected $_size;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_created;

    /**
     * @column
     * @readwrite
     * @type datetime
     */
    protected $_modified;

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
     * 
     * @return type
     */
    public function getFormatedSize($unit = 'kb')
    {
        $bytes = floatval($this->_size);

        $units = array(
            'b' => 1,
            'kb' => 1024,
            'mb' => pow(1024, 2),
            'gb' => pow(1024, 3)
        );

        $result = $bytes / $units[strtolower($unit)];
        $result = strval(round($result, 2)) . ' ' . strtoupper($unit);

        return $result;
    }

    /**
     * 
     * @return type
     */
    public function getUnlinkPath($type = true)
    {
        if ($type) {
            if (file_exists(APP_PATH . $this->_filepath)) {
                return APP_PATH . $this->_filepath;
            } elseif (file_exists('.' . $this->_filepath)) {
                return '.' . $this->_filepath;
            } elseif (file_exists('./' . $this->_filepath)) {
                return './' . $this->_filepath;
            }
        } else {
            return $this->_filepath;
        }
    }

    /**
     * 
     * @return type
     */
    public static function fetchAll()
    {
        $query = self::getQuery(array('dc.*'))
                ->join('tb_user', 'dc.userId = us.id', 'us', 
                        array('us.firstname', 'us.lastname'));
        $docs = self::initialize($query);

        return $docs;
    }

}
