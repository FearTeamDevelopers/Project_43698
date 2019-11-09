<?php

namespace THCFrame\Database\Connector;

use Mysqli;
use THCFrame\Database as Database;
use THCFrame\Database\Exception as Exception;
use THCFrame\Profiler\Profiler;
use THCFrame\Model\Model;
use THCFrame\Core\Core;

/**
 * The Database\Connector\Mysql class defines a handful of adaptable
 * properties and methods used to perform MySQLi class-specific functions,
 * and return MySQLi class-specific properties.
 */
class Mysql extends Database\Connector
{

    /**
     * @var null|MySQL
     */
    protected $service;

    /**
     * @readwrite
     */
    protected $host;

    /**
     * @readwrite
     */
    protected $username;

    /**
     * @readwrite
     */
    protected $password;

    /**
     * @readwrite
     */
    protected $schema;

    /**
     * @readwrite
     */
    protected $port = '3306';

    /**
     * @readwrite
     */
    protected $charset = 'utf8';

    /**
     * @readwrite
     */
    protected $engine = 'InnoDB';

    /**
     * @readwrite
     */
    protected $isConnected = false;

    /**
     * @read
     */
    protected $magicQuotesActive;

    /**
     * @read
     */
    protected $realEscapeStringExists;

    /**
     * Class constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->magicQuotesActive = get_magic_quotes_gpc();
        $this->realEscapeStringExists = function_exists('mysqli_real_escape_string');
    }

    public function __destruct()
    {
        $this->disconnect();
        $this->service = null;
    }

    /**
     * @param $query
     * @param bool $runQuery
     * @return array|null
     * @throws Exception\Service
     * @throws Exception\Sql
     * @throws \ReflectionException
     */
    private function runSyncQuery($query, $runQuery = true)
    {
        if ($runQuery === false) {
            return null;
        }

        Core::getLogger()->log($query, 'sync', false, 'DbModelSync.log');

        $result = $this->execute($query);
        if ($result === false) {
            $this->logError($this->service->error, $query);
            throw new Exception\Sql();
        }
        return $result;
    }

    /**
     *
     * @param string $error
     * @param string $sql
     */
    protected function logError($error, $sql)
    {
        $errMessage = sprintf('There was an error in the query %s', $error) . PHP_EOL;
        $errMessage .= 'SQL: ' . $sql;

        Core::getLogger()->log($errMessage);
    }

    /**
     * Method is used to ensure that the value of the
     * $service is a valid MySQLi instance
     *
     * @return boolean
     */
    protected function isValidService()
    {
        $isEmpty = empty($this->service);
        $isInstance = $this->service instanceof \MySQLi;

        if ($this->isConnected && $isInstance && !$isEmpty) {
            return true;
        }
        return false;
    }

    /**
     * Method attempts to connect to the MySQL server at the specified host/port
     *
     * @return Mysql
     * @throws Exception\Service
     */
    public function connect()
    {
        if (!$this->isValidService()) {
            $this->service = new MySQLi(
                $this->host, $this->username, $this->password, $this->schema, $this->port
            );

            if ($this->service->connect_error) {
                throw new Exception\Service('Unable to connect to database service: ' . $this->service->connect_error);
            }

            $this->service->set_charset('utf8');

            $this->isConnected = true;
            unset($this->password);
        }

        return $this;
    }

    /**
     * Method attempts to disconnect the $service instance from the MySQL service
     *
     * @return Mysql
     */
    public function disconnect()
    {
        if ($this->isValidService()) {
            $this->isConnected = false;
            $this->service->close();
        }

        return $this;
    }

    /**
     * Return query object for specific connector
     *
     * @return Database\Query\Mysql
     */
    public function query()
    {
        return new Database\Query\Mysql([
            'connector' => $this,
        ]);
    }

    /**
     * Method execute sql query by using prepared statements or simple
     * query method based on number of arguments
     *
     * @param string $sql
     * @return array|null
     * @throws Exception\Service
     * @throws Exception\Sql
     * @throws \ReflectionException
     */
    public function execute($sql)
    {
        if (!$this->isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        $profiler = Profiler::getInstance();

        $args = func_get_args();

        if (count($args) == 1) {
            $profiler->dbQueryStart($sql);
            $result = $this->service->query($sql);
            $profiler->dbQueryStop($this->getAffectedRows());

            return $result;
        }

        //$profiler->dbQueryStart($sql);
        if (!$stmt = $this->service->prepare($sql)) {
            $this->logError($this->service->error, $sql);

            if (ENV == Core::ENV_DEV) {
                throw new Exception\Sql(sprintf('There was an error in the query %s', $this->service->error));
            } else {
                throw new Exception\Sql('There was an error in the query');
            }
        }

        array_shift($args); //remove query from args

        $bindParamsReferences = [];

        foreach ($args as $key => $value) {
            $bindParamsReferences[$key] = &$args[$key];
        }

        $types = str_repeat('s', count($args)); //all params are strings, works well on MySQL and SQLite
        array_unshift($bindParamsReferences, $types);

        $bindParamsMethod = new \ReflectionMethod('mysqli_stmt', 'bind_param');
        $bindParamsMethod->invokeArgs($stmt, $bindParamsReferences);

        $stmt->execute();
        //$profiler->dbQueryStop($stmt->affected_rows);
        $meta = $stmt->result_metadata();

        unset($bindParamsMethod);

        if ($meta) {
            $stmtRow = [];
            $rowReferences = [];

            while ($field = $meta->fetch_field()) {
                $rowReferences[] = &$stmtRow[$field->name];
            }

            $bindResultMethod = new \ReflectionMethod('mysqli_stmt', 'bind_result');
            $bindResultMethod->invokeArgs($stmt, $rowReferences);

            $result = [];
            while ($stmt->fetch()) {
                foreach ($stmtRow as $key => $value) {
                    $row[$key] = $value;
                }
                $result[] = $row;
            }

            $stmt->free_result();
            $stmt->close();

            unset($stmt);
            unset($bindResultMethod);

            return $result;
        } else {
            return null;
        }
    }

    /**
     * Escapes values
     *
     * @param mixed $value
     * @return mixed
     * @throws Exception\Service
     */
    public function escape($value)
    {
        if (!$this->isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        if ($this->realEscapeStringExists) {
            if ($this->magicQuotesActive) {
                $value = stripslashes($value);
            }
            $value = $this->service->real_escape_string($value);
        } else {
            if (!$this->magicQuotesActive) {
                $value = addslashes($value);
            }
        }

        return $value;
    }

    /**
     * Returns last inserted id
     *
     * @return integer
     * @throws Exception\Service
     */
    public function getLastInsertId()
    {
        if (!$this->isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        return $this->service->insert_id;
    }

    /**
     * Returns count of affected rows by last query
     *
     * @return integer
     * @throws Exception\Service
     */
    public function getAffectedRows()
    {
        if (!$this->isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        return $this->service->affected_rows;
    }

    /**
     * Return last error
     *
     * @return string
     * @throws Exception\Service
     */
    public function getLastError()
    {
        if (!$this->isValidService()) {
            throw new Exception\Service('Not connected to a valid database service');
        }

        return $this->service->error;
    }

    /**
     * Begin transaction
     */
    public function beginTransaction()
    {
        $this->service->autocommit(false);
    }

    /**
     * Commit transaction
     */
    public function commitTransaction()
    {
        $this->service->commit();
        $this->service->autocommit(true);
    }

    /**
     * Rollback transaction
     */
    public function rollbackTransaction()
    {
        $this->service->rollback();
        $this->service->autocommit(true);
    }

    /**
     * Retrun columns in table with field name as key
     *
     * @param string $tableName
     * @return array
     * @throws Exception\Service
     * @throws Exception\Sql
     * @throws \ReflectionException
     */
    public function getColumns($tableName)
    {
        $sqlResult = $this->execute('SHOW FULL COLUMNS FROM ' . $tableName);
        $columns = [];

        if (!empty($sqlResult)) {
            while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
                $field = $row['Field'];
                unset($row['Field']);
                $columns[$field] = $row;
            }
        }

        return $columns;
    }

    /**
     * @param array $modelColumns
     * @param array $databaseColumns
     * @param $table
     * @return array
     */
    protected function dropColumns($modelColumns, $databaseColumns, $table)
    {
        $dropColumns = array_diff(array_keys($databaseColumns), array_keys($modelColumns));
        $queries = [];

        if (!empty($dropColumns)) {
            foreach ($dropColumns as $value) {
                $queries[] = "ALTER TABLE {$table} DROP COLUMN {$value};";
            }
        }

        return $queries;
    }

    /**
     * @param $table
     * @return array
     * @throws Exception\Service
     * @throws Exception\Sql
     * @throws \ReflectionException
     */
    protected function dropForeignKeys($table)
    {
        $fkResult = $this->execute(
            "select i.TABLE_NAME,i.COLUMN_NAME,i.CONSTRAINT_NAME,i.REFERENCED_TABLE_NAME,i.REFERENCED_COLUMN_NAME
                        from INFORMATION_SCHEMA.KEY_COLUMN_USAGE i
                        where i.TABLE_SCHEMA = '{$this->getSchema()}' and i.TABLE_NAME = '{$table}'
                        and i.referenced_column_name is not NULL;");

        $queries = [];

        if (!empty($fkResult)) {
            while ($row = $fkResult->fetch_array(MYSQLI_ASSOC)) {
                $queries[] = "ALTER TABLE {$table} DROP FOREIGN KEY {$row['CONSTRAINT_NAME']}";
            }
        }

        return $queries;
    }

    /**
     * Prepare queries to execute based on model structure.
     * Column definition is created by model variable annotations.
     *
     * @param Model $model
     * @param string $queryType
     * @param bool $dropIfExists
     * @return array
     */
    protected function prepareQueries(Model $model, $queryType = 'alter', $dropIfExists = true)
    {
        $lines = $queries = [];
        $createConstraints = [];

        $columns = $model->getColumns();
        $table = $model->getTable();
        $databaseColumnList = $this->getColumns($table);

        $queries += $this->dropColumns($columns, $databaseColumnList, $table);
        $queries += $this->dropForeignKeys($table);

        preg_match('/^([a-zA-Z]*).*/i', get_class($model), $matches);
        $tableComment = strtolower($matches[1]);
        unset($matches);

        $templateCreate = "CREATE TABLE `%s` (\n%s,\n%s\n) ENGINE=%s DEFAULT CHARSET=%s COMMENT='%s';";
        $templateAlter = "ALTER TABLE `%s` %s;";

        foreach ($columns as $column) {
            $raw = $column['raw'];
            $name = $column['name'];
            $type = $column['type'];
            $length = $column['length'];

            if ($queryType == 'alter') {
                $alterType = $this->getTypeOfAlter($name, $databaseColumnList);
            } else {
                $alterType = '';
            }

            if ($column['default'] !== false) {
                if ($column['default'] == 'null') {
                    $default = "DEFAULT NULL";
                } elseif ((int)$column['default'] === 0 && in_array($type,
                        ['int', 'integer', 'tinyint', 'smallint', 'mediumint'])) {
                    $default = 'DEFAULT 0';
                } elseif ((int)$column['default'] === 0 && in_array($type, ['float', 'double', 'decimal'])) {
                    $default = 'DEFAULT 0.0';
                } elseif (is_numeric($column['default'])) {
                    $default = "DEFAULT {$column['default']}";
                } else {
                    $default = "DEFAULT '{$column['default']}'";
                }
            } else {
                $default = '';
            }

            $null = empty($column['null']) && strpos($type, 'text') === false ? 'NOT NULL' : '';
            $unsigned = $column['unsigned'] === true ? 'UNSIGNED' : '';

            $cmStr = $column['validate'] !== false ? '@validate ' . implode(',', $column['validate']) . ';' : '';
            $cmStr .= !empty($column['label']) ? '@label ' . $column['label'] . ';' : '';
            $comment = $cmStr === '' ? '' : "COMMENT '{$cmStr}'";

            switch ($type) {
                case 'auto_increment':
                    {
                        $lines[] = "{$alterType} `{$name}` int(11) UNSIGNED NOT NULL AUTO_INCREMENT";
                        break;
                    }
                default:
                    {
                        if ($length !== null) {
                            $lines[] = preg_replace('/\s+/', ' ',
                                "{$alterType} `{$name}` {$type}({$length}) {$unsigned} {$null} {$default} {$comment}");
                        } else {
                            $lines[] = preg_replace('/\s+/', ' ',
                                "{$alterType} `{$name}` {$type} {$unsigned} {$null} {$default} {$comment}");
                        }
                        break;
                    }
            }

            if ($column['primary']) {
                $createConstraints[] = "PRIMARY KEY (`{$name}`)";
            }
            if ($column['index']) {
                $createConstraints[] = "KEY `ix_{$name}` (`{$name}`)";
            }
            if ($column['unique']) {
                $createConstraints[] = "UNIQUE KEY (`{$name}`)";
            }
            if (!empty($column['foreign'])) {
                preg_match('/^([a-zA-Z_-]*)\s?REFERENCES ([a-zA-Z_-]*) \(([a-zA-Z_,-]*)\) (.*)$/i', $column['foreign'],
                    $fkParts);

                $fkName = !empty($fkParts[1]) ? "`{$fkParts[1]}`" : '';
                $referencedTable = $fkParts[2];
                $referencedColumn = $fkParts[3];
                $referenceDefinition = $fkParts[4];

                $createConstraints[] = preg_replace('/\s+/', ' ',
                    "FOREIGN KEY {$fkName} (`{$name}`) REFERENCES `{$referencedTable}` (`{$referencedColumn}`) {$referenceDefinition}");
                unset($fkParts);
            }
            if (!empty($column['foreign']) && $queryType == 'alter') {
                $lines[] = preg_replace('/\s+/', ' ',
                    "ADD FOREIGN KEY {$fkName} (`{$name}`) REFERENCES `{$referencedTable}` (`{$referencedColumn}`) {$referenceDefinition}");
            }
        }

        if ($queryType == 'create') {
            if ($dropIfExists === true) {
                $queries[] = "DROP TABLE IF EXISTS {$table};";
            }
            $queries[] = sprintf(
                $templateCreate, $table, implode(",\n", $lines), implode(",\n", $createConstraints), $this->engine,
                $this->charset, $tableComment
            );
        } elseif ($queryType == 'alter') {
            if (!empty($lines)) {
                foreach ($lines as $columnDef) {
                    $queries[] = sprintf($templateAlter, $table, $columnDef);
                }
            }
        }

        unset($lines, $createConstraints, $model, $databaseColumnList);

        return $queries;
    }

    /**
     * Get type of alter. If column exists in database return modify.
     * If column does not exists in database return add.
     *
     * @param string $columnName
     * @param array $databaseColumns
     * @return string
     */
    protected function getTypeOfAlter($columnName, $databaseColumns)
    {
        if (array_key_exists($columnName, $databaseColumns)) {
            return 'MODIFY COLUMN';
        } elseif (!array_key_exists($columnName, $databaseColumns)) {
            return 'ADD COLUMN';
        }
    }

    /**
     * Method converts the class/properties into a valid SQL query, and
     * into a physical database table. It does this by first
     * getting a list of the columns, by calling the model’s getColumns() method.
     * Looping over the columns, it creates arrays of indices and field strings.
     * After all the field strings have been created, they are joined (along with the indices),
     * and applied to the CREATE TABLE or ALTER TABLE $template string.
     *
     * @param Model $model
     * @return Mysql
     * @throws Exception\Sql
     */
    public function sync(Model $model, $runQuery = true, $queryType = 'alter', $dropIfExists = true)
    {
        Core::getLogger()->log('---------- ' . get_class($model) . ' - Sync Start ----------', 'sync', true,
            'DbModelSync.log');

        $queries = $this->prepareQueries($model, $queryType, $dropIfExists);

        try {
            $this->beginTransaction();

            $this->execute('SET foreign_key_checks = 0;');

            if (!empty($queries)) {
                foreach ($queries as $sql) {
                    $this->runSyncQuery($sql, $runQuery);
                }
            }

            $this->execute('SET foreign_key_checks = 1;');
        } catch (\Exception $ex) {
            Core::getLogger()->log($ex->getMessage(), 'sync', true, 'DbModelSync.log');
            Core::getLogger()->log('---------- ' . get_class($model) . ' - Sync was finished with errors ----------',
                'sync', true, 'DbModelSync.log');
            $this->rollbackTransaction();
            return false;
        }

        $this->commitTransaction();
        Core::getLogger()->log('---------- ' . get_class($model) . ' - Sync was finished without errors ----------',
            'sync', true, 'DbModelSync.log');

        return true;
    }

    /**
     * Returns table size in MB
     */
    public function getDatabaseSize()
    {
        $sql = "SHOW TABLE STATUS FROM `" . $this->schema . "`";
        $result = $this->execute($sql);

        if ($result !== false) {
            $size = 0;

            while ($row = $result->fetch_array(MYSQLI_ASSOC)) {
                $size += $row["Data_length"] + $row["Index_length"];
            }

            $megabytes = $size / (1024 * 1024);
            return number_format(round($megabytes, 3), 2);
        }

        return 0;
    }

    /**
     *
     * @return boolean
     */
    public function ping()
    {
        if ($this->isValidService()) {
            return $this->service->ping();
        }

        return false;
    }

    public function getService()
    {
        return $this->service;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getCharset()
    {
        return $this->charset;
    }

    public function getEngine()
    {
        return $this->engine;
    }

    public function getIsConnected()
    {
        return $this->isConnected;
    }

    public function getMagicQuotesActive()
    {
        return $this->magicQuotesActive;
    }

    public function getRealEscapeStringExists()
    {
        return $this->realEscapeStringExists;
    }

    public function setService($service)
    {
        $this->service = $service;
        return $this;
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
        return $this;
    }

    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    public function setEngine($engine)
    {
        $this->engine = $engine;
        return $this;
    }

    public function setIsConnected($isConnected)
    {
        $this->isConnected = $isConnected;
        return $this;
    }

    public function setMagicQuotesActive($magicQuotesActive)
    {
        $this->magicQuotesActive = $magicQuotesActive;
        return $this;
    }

    public function setRealEscapeStringExists($realEscapeStringExists)
    {
        $this->realEscapeStringExists = $realEscapeStringExists;
        return $this;
    }

}
