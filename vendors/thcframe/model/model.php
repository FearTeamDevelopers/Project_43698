<?php

namespace THCFrame\Model;

use THCFrame\Core\Base;
use THCFrame\Database\Connector;
use THCFrame\Database\Query;
use THCFrame\Registry\Registry;
use THCFrame\Core\Inspector;
use THCFrame\Core\StringMethods;

/**
 * This class allow us to isolate all the direct database communication,
 * and most communication with third-party web services. Models can connect to any
 * number of third-party data services, and provide a simple interface for use in our controllers.
 *
 * An ORM library creates an opaque communication layer between two data-related systems
 */
class Model extends Base
{

    /**
     * @readwrite
     */
    protected $_table;

    /**
     * @readwrite
     */
    protected $_alias = '';

    /**
     * @readwrite
     */
    protected $_connector;

    /**
     * In case of use multidb model have to has set database identificator
     * Method getConnector then uses this to select correct database connector
     *
     * @readwrite
     */
    protected $_databaseIdent = null;

    /**
     * @read
     */
    protected $_types = [
        'auto_increment',
        'binary',
        'char',
        'varchar',
        'text',
        'mediumtext',
        'longtext',
        'blob',
        'mediumblob',
        'longblob',
        'tinyint',
        'smallint',
        'mediumint',
        'int',
        'integer',
        'float',
        'double',
        'decimal',
        'boolean',
        'date',
        'time',
        'datetime',
    ];

    /**
     * @read
     */
    protected $_validators = [
        'required' => [
            'handler' => '_validateRequired',
            'message_en' => 'The {0} field is required',
            'message_cs' => 'Pole {0} je povinné',
        ],
        'alpha' => [
            'handler' => '_validateAlpha',
            'message_en' => 'The {0} field can only contain letters',
            'message_cs' => 'Pole {0} může obsahovat pouze písmena',
        ],
        'numeric' => [
            'handler' => '_validateNumeric',
            'message_en' => 'The {0} field can only contain numbers',
            'message_cs' => 'Pole {0} může obsahovat pouze číslice',
        ],
        'alphanumeric' => [
            'handler' => '_validateAlphaNumeric',
            'message_en' => 'The {0} field can only contain letters and numbers',
            'message_cs' => 'Pole {0} může obsahovat pouze písmena a čísla',
        ],
        'max' => [
            'handler' => '_validateMax',
            'message_en' => 'The {0} field must contain less than {2} characters',
            'message_cs' => 'Pole {0} musí obsahovat méně než {2} znaků',
        ],
        'min' => [
            'handler' => '_validateMin',
            'message_en' => 'The {0} field must contain more than {2} characters',
            'message_cs' => 'Pole {0} musí obsahovat více než {2} znaků',
        ],
        'email' => [
            'handler' => '_validateEmail',
            'message_en' => 'The {0} field must contain valid email address',
            'message_cs' => 'Pole {0} musí obsahovat validní emailovou adresu',
        ],
        'url' => [
            'handler' => '_validateUrl',
            'message_en' => 'The {0} field must contain valid url',
            'message_cs' => 'Pole {0} musí obsahovat validní url adresu',
        ],
        'datetime' => [
            'handler' => '_validateDatetime',
            'message_en' => 'The {0} field must contain valid date and time (yyyy-mm-dd hh:mm)',
            'message_cs' => 'Pole {0} musí obsahovat datum a čas ve formátu (yyyy-mm-dd hh:mm)',
        ],
        'date' => [
            'handler' => '_validateDate',
            'message_en' => 'The {0} field must contain valid date (yyyy-mm-dd)',
            'message_cs' => 'Pole {0} musí obsahovat datum ve formátu (yyyy-mm-dd)',
        ],
        'time' => [
            'handler' => '_validateTime',
            'message_en' => 'The {0} field must contain valid time (hh:mm / hh:mm:ss)',
            'message_cs' => 'Pole {0} musí obsahovat čas ve formátu (hh:mm / hh:mm:ss)',
        ],
        'html' => [
            'handler' => '_validateHtml',
            'message_en' => 'The {0} field can contain these tags only (span,strong,em,s,p,div,a,ol,ul,li,img,table,caption,thead,tbody,tr,td,br,hr)',
            'message_cs' => 'Pole {0} může obsahovat následující html tagy (span,strong,em,s,p,div,a,ol,ul,li,img,table,caption,thead,tbody,tr,td,br,hr)',
        ],
        'json' => [
            'handler' => '_validateJson',
            'message_en' => 'The {0} must contain valid JSON string',
            'message_cs' => 'Pole {0} musí obsahovat řetězec v JSON formátu',
        ],
        'path' => [
            'handler' => '_validatePath',
            'message_en' => 'The {0} field must contain filesystem path',
            'message_cs' => 'Pole {0} musí obsahovat validní cestu',
        ],
    ];

    /**
     * @read
     */
    protected $_errors = [];
    protected $_columns;
    protected $_primary;

    /**
     *
     * @param $method
     * @return \THCFrame\Model\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * @param $value
     * @return bool
     */
    protected function _validateRequired($value)
    {
        return !empty($value) || is_numeric($value);
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function _validateAlpha($value)
    {
        if ($value === '') {
            return true;
        }

        $pattern = preg_quote('#$%^&*()+=-[]\',./|\":?~_', '#');
        return StringMethods::match($value, "#([a-zá-žA-ZÁ-Ž{$pattern}]+)#");
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function _validateNumeric($value)
    {
        if ($value === '') {
            return true;
        }

        $pattern = preg_quote('-,.\s', '#');
        return StringMethods::match($value, "#([0-9{$pattern}]+)#");
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function _validateAlphaNumeric($value)
    {
        if ($value === '') {
            return true;
        }

        $pattern = preg_quote('#$%^&*()+=-[]\',./|\":?~_', '#');
        return StringMethods::match($value, "#([a-zá-žA-ZÁ-Ž0-9{$pattern}]+)#");
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function _validateJson($value)
    {
        if ($value === '') {
            return true;
        }

        $pattern = preg_quote('#$%^&*()+=-[]\',./|\":?~_{}', '#');
        return StringMethods::match($value, "#([a-zá-žA-ZÁ-Ž0-9{$pattern}]+)#");
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function _validateHtml($value)
    {
        if ($value === '') {
            return true;
        }

        $pattern = preg_quote('#$%^&*()+=-[]\',./|\":?~_', '#');
        return StringMethods::match($value,
            '#((<|&lt;)(strong|em|s|p|div|a|img|table|tr|td|thead|tbody|ol|li|ul|caption|span|br|hr)(\/)?(>|&gt;)'
            . "[a-zá-žA-ZÁ-Ž0-9{$pattern}]+)*"
            . "[a-zá-žA-ZÁ-Ž0-9{$pattern}]+#");
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function _validatePath($value)
    {
        if ($value === '') {
            return true;
        }

        $pattern = preg_quote('()-./:_', '#');
        return StringMethods::match($value, "#^([a-zA-Z0-9{$pattern}]+)$#");
    }

    /**
     * @param $value
     * @param $number
     * @return bool
     */
    protected function _validateMax($value, $number)
    {
        return mb_strlen($value) <= (int)$number;
    }

    /**
     * @param $value
     * @param $number
     * @return bool
     */
    protected function _validateMin($value, $number)
    {
        return mb_strlen($value) >= (int)$number;
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function _validateEmail($value)
    {
        if ($value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * @param $value
     * @return bool|mixed
     */
    protected function _validateUrl($value)
    {
        if ($value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * @param $value
     * @return bool
     */
    protected function _validateDatetime($value)
    {
        if ($value === '') {
            return true;
        }

        list($date, $time) = explode(' ', $value);

        $validDate = $this->_validateDate($date);
        $validTime = $this->_validateTime($time);

        return $validDate && $validTime;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function _validateDate($value)
    {
        if ($value === '') {
            return true;
        }

        $config = Registry::get('configuration');
        $format = $config->system->dateformat;

        if (strlen($value) >= 6 && strlen($format) == 10) {

            $separator_only = str_replace(['m', 'd', 'y'], '', $format);
            $separator = $separator_only[0]; // separator is first character

            if ($separator && strlen($separator_only) == 2) {
                $regexp = str_replace('mm', '(0?[1-9]|1[0-2])', $format);
                $regexp = str_replace('dd', '(0?[1-9]|[1-2][0-9]|3[0-1])', $regexp);
                $regexp = str_replace('yyyy', '(19|20)?[0-9][0-9]', $regexp);
                //$regexp = str_replace($separator, "\\" . $separator, $regexp);

                if ($regexp != $value && preg_match('/' . $regexp . '\z/', $value)) {
                    $arr = explode($separator, $value);
                    $day = $arr[2];
                    $month = $arr[1];
                    $year = $arr[0];

                    if (@checkdate($month, $day, $year)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @param $value
     * @return bool|false|int
     */
    protected function _validateTime($value)
    {
        if ($value === '') {
            return true;
        }

        return preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value);
    }

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        //$this->load();
    }

    /**
     * Object destructor
     */
    public function __destruct()
    {
        unset($this->_connector);
    }

    /**
     * Method simplifies record retrieval for us.
     * It determines the model’s primary column and checks to see whether
     * it is not empty. This tells us whether the primary key has been provided,
     * which gives us aviable means of finding the intended record.
     *
     * If the primary key class property is empty, we assume this model instance
     * is intended for the creation of a new record, and do nothing further.
     * To load the database record, we get the current model’s connector,
     * which halts execution if none is found. We create a database query
     * for the record, based on the primary key column property’s value.
     *
     * If no record is found, the Model\Exception\Primary exception is raised.
     * This happens when a primary key column value is provided,
     * but does not represent a valid identifier for a record in the database table.
     *
     * Finally we loop over the loaded record’s data and only set property
     * values that were not set in the __construct() method.
     *
     * @throws Exception\Connector
     * @throws Exception\Implementation
     * @throws Exception\Primary
     */
    public function load()
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        if (!empty($this->$raw)) {
            $previous = $this->getConnector()
                ->query()
                ->from($this->getTable())
                ->setTableAlias($this->alias)
                ->where("{$name} = ?", $this->$raw)
                ->first();

            if ($previous == null) {
                throw new Exception\Primary('Primary key value invalid');
            }

            foreach ($previous as $key => $value) {
                $prop = "_{$key}";
                if (!empty($previous->$key) && !isset($this->$prop)) {
                    $this->$key = $previous->$key;
                }
            }
        }
    }

    /**
     * @return mixed
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    public function delete()
    {
        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        if (!empty($this->$raw)) {
            $this->getConnector()->beginTransaction();

            $query = $this->getConnector()
                ->query()
                ->from($this->getTable())
                ->where("{$name} = ?", $this->$raw);

            $state = $query->delete();

            unset($query);

            if ($state !== -1) {
                $this->getConnector()->commitTransaction();
                return $state;
            }

            $this->getConnector()->rollbackTransaction();
            return $state;
        }
    }

    /**
     * @param array $where
     * @return mixed
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    public static function deleteAll($where = [])
    {
        $instance = new static();

        $query = $instance->getConnector()
            ->query()
            ->from($instance->getTable());

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        $instance->getConnector()->beginTransaction();

        $state = $query->delete();

        unset($query);

        if ($state != -1) {
            $instance->getConnector()->commitTransaction();
            return $state;
        } else {
            $instance->getConnector()->rollbackTransaction();
            return $state;
        }
    }

    /**
     * @param array $where
     * @param array $data
     * @return mixed
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    public static function updateAll($where = [], $data = [])
    {
        $instance = new static();

        $query = $instance->getConnector()
            ->query()
            ->from($instance->getTable());

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        $instance->getConnector()->beginTransaction();

        $state = $query->update($data);

        unset($query);

        if ($state !== -1) {
            $instance->getConnector()->commitTransaction();
            return $state;
        }

        $instance->getConnector()->rollbackTransaction();
        return $state;
    }

    /**
     *
     */
    public function preSave()
    {

    }

    /**
     *
     */
    public function postSave()
    {

    }

    /**
     * Method creates a query instance, and targets the table related to the Model class.
     * It applies a WHERE clause if the primary key property value is not empty,
     * and builds a data array based on columns returned by the getColumns() method.
     *
     * Finally, it calls the query instance’s save()method to commit the
     * data to the database. Since the Database\Connector class executes
     * either an INSERT or UPDATE statement, based on the WHERE clause criteria,
     * this method will either insert a new record, or update an existing record,
     * depending on whether the primary key property has a value or not.
     *
     * @return mixed
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    public function save()
    {
        $this->preSave();

        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        $query = $this->getConnector()
            ->query()
            ->from($this->getTable());

        if (!empty($this->$raw)) {
            $query->where("{$name} = ?", $this->$raw);
        }

        $data = [];
        foreach ($this->columns as $key => $column) {
            if (!$column['read']) {
                $prop = $column['raw'];
                $data[$key] = $this->$prop;
                continue;
            }

            if ($column != $this->primaryColumn && $column) {
                $method = 'get' . ucfirst($key);
                $data[$key] = $this->$method();
                continue;
            }
        }

        $result = $query->save($data);

        unset($query);

        if ($result > 0) {
            $this->$raw = $result;
        }

        $this->postSave();

        return $result;
    }

    /**
     *
     */
    public function preUpdate()
    {

    }

    /**
     *
     */
    public function postUpdate()
    {

    }

    /**
     * Method creates a query instance, and targets the table related to the Model class.
     * It applies a WHERE clause if the primary key property value is not empty,
     * and builds a data array based on columns returned by the getColumns() method.
     *
     * Finally, it calls the query instance’s update() method to commit the
     * data to the database. This method will update an existing record,
     * depending on whether the primary key property has a value.
     *
     * @return bool
     * @throws Exception\Connector
     * @throws Exception\Implementation
     * @throws Exception\Primary
     */
    public function update()
    {
        $this->preUpdate();

        $primary = $this->primaryColumn;

        $raw = $primary['raw'];
        $name = $primary['name'];

        $query = $this->getConnector()
            ->query()
            ->from($this->getTable());

        if (!empty($this->$raw)) {
            $query->where("{$name} = ?", $this->$raw);
        } else {
            throw new Exception\Primary('Primary key is not set');
        }

        $data = [];
        foreach ($this->columns as $key => $column) {
            if (!$column['read']) {
                $prop = $column['raw'];
                $data[$key] = $this->$prop;
                continue;
            }

            if ($column != $this->primaryColumn && $column) {
                $method = 'get' . ucfirst($key);

                if ($this->$method() !== null) {
                    $data[$key] = $this->$method();
                }
                continue;
            }
        }

        $result = $query->update($data);

        $this->postUpdate();
        unset($query);

        return $result > 0;
    }

    /**
     * Method returns a user-defined table name based on the current
     * Model’s class name (using PHP’s get_class() method
     *
     * @return string
     * @throws Exception\Implementation
     */
    public function getTable()
    {
        if (empty($this->_table)) {
            if ($this->_databaseIdent === null) {
                $tablePrefix = Registry::get('configuration')->database->main->tablePrefix;
            } else {
                $tablePrefix = Registry::get('configuration')->database->{$this->_databaseIdent}->tablePrefix;
            }

            if (preg_match('#model#i', get_class($this))) {
                $parts = array_reverse(explode('\\', get_class($this)));
                $this->_table = str_replace(['Basic', 'basic'], '',
                    strtolower($tablePrefix . mb_eregi_replace('model', '', $parts[0])));
            } else {
                throw new Exception\Implementation('Model has not valid name used for THCFrame\Model\Model');
            }
        }

        return $this->_table;
    }

    /**
     * Method so that we can return the contents of the $_connector property,
     * a connector instance stored in the Registry class, or raise a Model\Exception\Connector
     *
     * @return Connector
     * @throws Exception\Connector
     */
    public function getConnector()
    {
        if (empty($this->_connector)) {
            if ($this->_databaseIdent === null) {
                $dbIdent = 'main';
            } else {
                $dbIdent = strtolower($this->_databaseIdent);
            }

            try {
                $database = Registry::get('database')->get($dbIdent);

                if ($database->ping() === false) {
                    $backupDb = Registry::get('database')->get('backup');

                    if ($backupDb->ping() === false) {
                        throw new Exception\Connector('No connector availible');
                    } else {
                        $this->_connector = $backupDb;
                    }
                } else {
                    $this->_connector = $database;
                }
            } catch (Exception $ex) {
                throw new Exception\Connector($ex->getMessage());
            }
        }

        return $this->_connector;
    }

    /**
     * Method creates an Inspector instance and a utility function ($first) to return the
     * first item in a metadata array. Next, it loops through all the properties in the model,
     * and sifts out all that have an @column flag. Any other properties are ignored at this point.
     * The column’s
     *
     * @return array
     * @throws Exception\Primary
     * @throws Exception\Type
     * @throws \ReflectionException
     */
    public function getColumns()
    {
        if (empty($this->_columns)) {
            $primaries = 0;
            $columns = [];
            $class = get_class($this);
            $types = $this->_types;

            $inspector = new Inspector($this);
            $properties = $inspector->getClassProperties();

            $first = function ($array, $key) {
                if (!empty($array[$key]) && !is_array($array[$key])) {
                    return $array[$key];
                }
                if (!empty($array[$key]) && count($array[$key]) == 1) {
                    return $array[$key][0];
                }
                return null;
            };

            foreach ($properties as $property) {
                $propertyMeta = $inspector->getPropertyMeta($property);

                if (!empty($propertyMeta['@column'])) {
                    $name = mb_ereg_replace('^_', '', $property);
                    $primary = !empty($propertyMeta['@primary']);
                    $type = $first($propertyMeta, '@type');
                    $length = $first($propertyMeta, '@length');
                    $default = !empty($propertyMeta['@default']) ? $first($propertyMeta, '@default') : false;
                    $null = !empty($propertyMeta['@null']);
                    $unsigned = !empty($propertyMeta['@unsigned']);
                    $index = !empty($propertyMeta['@index']);
                    $unique = !empty($propertyMeta['@unique']);
                    $readwrite = !empty($propertyMeta['@readwrite']);
                    $read = !empty($propertyMeta['@read']) || $readwrite;
                    $write = !empty($propertyMeta['@write']) || $readwrite;

                    $validate = !empty($propertyMeta['@validate']) ? $propertyMeta['@validate'] : false;
                    $label = $first($propertyMeta, '@label');
                    $foreign = !empty($propertyMeta['@foreign']) ? $first($propertyMeta, '@foreign') : false;

                    if (!in_array($type, $types)) {
                        throw new Exception\Type(sprintf('%s is not a valid type', $type));
                    }

                    if ($primary) {
                        $primaries++;
                    }

                    $columns[$name] = [
                        'raw' => $property,
                        'name' => $name,
                        'primary' => $primary,
                        'foreign' => $foreign,
                        'type' => $type,
                        'length' => $length,
                        'default' => $default,
                        'index' => $index,
                        'unique' => $unique,
                        'null' => $null,
                        'unsigned' => $unsigned,
                        'read' => $read,
                        'write' => $write,
                        'validate' => $validate,
                        'label' => $label,
                    ];

                }
            }

            if ($primaries !== 1) {
                throw new Exception\Primary(sprintf('%s must have exactly one @primary column', $primary));
            }

            $this->_columns = $columns;
        }

        return $this->_columns;
    }

    /**
     * Method returns a column by name. Class properties are assumed to begin
     * with an underscore (_) character. This assumption is continued by the
     * getColumn() method, which checks for a column without the _ character.
     * When declared as a column property, columns will look like _firstName,
     * but referenced by any public getters/setters/methods,
     * they will look like setFirstName/firstName.
     *
     * @param string $name
     * @return mixed
     */
    public function getColumn($name)
    {
        if (!empty($this->_columns[$name])) {
            return $this->_columns[$name];
        }
        return null;
    }

    /**
     * Method loops through the columns, returning the one marked as primary
     *
     * @return mixed
     */
    public function getPrimaryColumn()
    {
        if (!isset($this->_primary)) {
            $primary = null;

            foreach ($this->columns as $column) {
                if ($column['primary']) {
                    $primary = $column;
                    break;
                }
            }

            $this->_primary = $primary;
        }

        return $this->_primary;
    }

    /**
     * Method is a simple, static wrapper method for the protected _first() method
     *
     * @param array $where
     * @param array $fields
     * @param array $order
     * @return mixed
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    public static function first($where = [], $fields = ['*'], $order = [])
    {
        $model = new static();
        return $model->_first($where, $fields, $order);
    }

    /**
     * Method returns the first matched record
     *
     * @param array $where
     * @param array $fields
     * @param array $order
     * @return mixed
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    protected function _first($where = [], $fields = ['*'], $order = [])
    {
        $query = $this->getConnector()
            ->query()
            ->from($this->getTable(), $fields)
            ->setTableAlias($this->alias);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if (!empty($order)) {
            foreach ($order as $filed => $direction) {
                $query->order($filed, $direction);
            }
        }

        $first = $query->first();
        $class = get_class($this);

        unset($query);

        if ($first) {
            return new $class($first);
        }

        return null;
    }

    /**
     * Method is a simple, static wrapper method for the protected _all() method
     *
     * @param array $where
     * @param array $fields
     * @param array $order
     * @param int $limit
     * @param int $page
     * @param null $group
     * @param array $having
     * @return array|null
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    public static function all(
        $where = [],
        $fields = ['*'],
        $order = [],
        $limit = null,
        $page = null,
        $group = null,
        $having = []
    ) {
        $model = new static();
        return $model->_all($where, $fields, $order, $limit, $page, $group, $having);
    }

    /**
     * Method creates a query, taking into account the various filters and flags,
     * to return all matching records. The reason we go to the trouble of
     * wrapping an instance method within a static method is because we have
     * created a context wherein a model instance is equal to a database record.
     * Multirecord operations make more sense as class methods, in this context.
     *
     * @param array $where
     * @param array $fields
     * @param array $order
     * @param null|int $limit
     * @param null|int $page
     * @param null $group
     * @param array $having
     * @return array|null
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    protected function _all(
        $where = [],
        $fields = ['*'],
        $order = [],
        $limit = null,
        $page = null,
        $group = null,
        $having = []
    ) {
        $query = $this->getConnector()
            ->query()
            ->from($this->getTable(), $fields)
            ->setTableAlias($this->alias);

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        if ($group != null) {
            $query->groupby($group);

            if (!empty($having)) {
                foreach ($having as $clause => $value) {
                    $query->having($clause, $value);
                }
            }
        }

        if (!empty($order)) {
            foreach ($order as $filed => $direction) {
                $query->order($filed, $direction);
            }
        }

        if ($limit != null) {
            $query->limit($limit, $page);
        }

        $rows = [];
        $class = get_class($this);

        foreach ($query->all() as $row) {
            $rows[] = new $class($row);
        }

        unset($query);

        if (empty($rows)) {
            return null;
        } else {
            return $rows;
        }
    }

    /**
     * Method is a simple, static wrapper method for the protected _getQuery() method
     *
     * @param $fields
     * @return mixed
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    public static function getQuery($fields)
    {
        $model = new static();
        return $model->_getQuery($fields);
    }

    /**
     * Method return new query instance for current model
     *
     * @param $fields
     * @return mixed
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    protected function _getQuery($fields)
    {
        return $this->getConnector()
            ->query()
            ->from($this->getTable(), $fields)
            ->setTableAlias($this->alias);
    }

    /**
     *
     * @param Query $query
     * @return null|array
     */
    public static function initialize(Query $query)
    {
        $model = new static();
        $rows = [];
        $class = get_class($model);

        foreach ($query->all() as $row) {
            $rows[] = new $class($row);
        }

        unset($query);

        if (empty($rows)) {
            return null;
        } else {
            return $rows;
        }
    }

    /**
     * Method is a simple, static wrapper method for the protected _count() method
     *
     * @param array $where
     * @return int
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    public static function count($where = [])
    {
        $model = new static();
        return $model->_count($where);
    }

    /**
     * Method returns a count of the matched records
     *
     * @param array $where
     * @return int
     * @throws Exception\Connector
     * @throws Exception\Implementation
     */
    protected function _count($where = [])
    {
        $query = $this
            ->getConnector()
            ->query()
            ->from($this->getTable());

        foreach ($where as $clause => $value) {
            $query->where($clause, $value);
        }

        return $query->count();
    }

    /**
     * Method begins by getting a list of columns and iterating over that list.
     * For each column, we determine whether validation should occur.
     * We then split the @validate metadata into a list of validation conditions.
     * If a condition has arguments (e.g., max(100)), we extract the arguments.
     * We then run each validation method on the column data and generate error
     * messages for those validation conditions that failed.
     * We return a final true/false to indicate whether the complete validation passed or failed.
     *
     * @return bool
     * @throws Exception\Validation
     */
    public function validate()
    {
        $this->_errors = [];
        $config = Registry::get('configuration');
        $errLang = $config->system->lang;

        foreach ($this->columns as $column) {
            if ($column['validate']) {
                $pattern = '#[a-z]+\(([a-zá-žA-ZÁ-Ž0-9, ]+)\)#';

                $raw = $column['raw'];
                $name = $column['name'];
                $validators = $column['validate'];
                $label = $column['label'];

                $defined = $this->getValidators();

                foreach ($validators as $validator) {
                    $function = $validator;
                    $arguments = [
                        $this->$raw,
                    ];

                    $match = StringMethods::match($validator, $pattern);

                    if (count($match) > 0) {
                        $matches = StringMethods::split($match[0], ',\s*');
                        $arguments = array_merge($arguments, $matches);
                        $offset = StringMethods::indexOf($validator, '(');
                        $function = substr($validator, 0, $offset);
                    }

                    if (!isset($defined[$function])) {
                        throw new Exception\Validation(sprintf('The %s validator is not defined', $function));
                    }

                    $template = $defined[$function];

                    if (!call_user_func_array([$this, $template['handler']], $arguments)) {
                        $replacements = array_merge([
                            $label ? $label : $raw,
                        ], $arguments);

                        $message = $template['message_' . $errLang];

                        foreach ($replacements as $i => $replacement) {
                            $message = str_replace("{{$i}}", $replacement, $message);
                        }

                        if (!isset($this->_errors[$name])) {
                            $this->_errors[$name] = [];
                        }

                        $this->_errors[$name][] = $message;
                    }
                }
            }
        }

        return !count($this->errors);
    }

    public function __toString()
    {
        return get_class($this);
    }

}
