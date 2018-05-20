<?php

namespace THCFrame\Model;

use THCFrame\Core\Base;
use THCFrame\Registry\Registry;
use THCFrame\Model\Modelwriter;
use THCFrame\Model\Exception;
use THCFrame\Core\Core;

/**
 * Class generate new class files based on database structure
 *
 * @author Tomy
 */
class Generator extends Base
{

    /**
     * @readwrite
     * @var type
     */
    protected $dbIdent;

    /**
     * @readwrite
     * @var type
     */
    protected $dbSchema;

    /**
     *
     * @var THCFrame\Database\Connector
     */
    private $db;

    /**
     *
     * @var THCFrame\Database\ConnectionHandler
     */
    private $connectionHandler;

    /**
     *
     * @param type $options
     */
    public function __construct($options = [])
    {
        $this->connectionHandler = Registry::get('database');

        parent::__construct($options);
        $ident = $this->dbIdent;

        $this->db = $this->connectionHandler->get($ident);
        $this->dbSchema = Registry::get('configuration')->database->$ident->schema;
    }

    /**
     * Get table prefix from system configuration
     *
     * @return string
     */
    private function getTablePrefix()
    {
        $ident = $this->dbIdent;
        $tbPrefix = Registry::get('configuration')->database->$ident->tablePrefix;

        return $tbPrefix;
    }

    /**
     * Get tables from database with system specific prefix
     *
     * @return array
     */
    private function getTables()
    {
        $sqlResult = $this->db->execute('SHOW TABLE STATUS IN ' . $this->getDbSchema() . " LIKE '" . $this->getTablePrefix() . "%'");
        $tables = [];

        $moduleNames = Core::getModuleNames(true);

        if (!empty($sqlResult)) {
            while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
                if (!in_array(strtolower($row['Comment']), $moduleNames)) {
                    continue;
                }

                $tables[$row['Name']] = $row['Comment'];
            }
        }

        return $tables;
    }

    /**
     * Get columns of table
     *
     * @param string $tableName
     * @return array
     */
    private function getTableColumns($tableName)
    {
        $sqlResult = $this->db->execute('SHOW FULL COLUMNS FROM ' . $tableName);
        $columns = [];

        while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
            if (strtolower($row['Key']) == 'mul') {
                $fkResult = $this->db->execute(
                        "select i.TABLE_NAME,i.COLUMN_NAME,i.CONSTRAINT_NAME,i.REFERENCED_TABLE_NAME,i.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE
                        from INFORMATION_SCHEMA.KEY_COLUMN_USAGE as i
                        LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS r
                        ON r.CONSTRAINT_SCHEMA=i.TABLE_SCHEMA
                            AND r.CONSTRAINT_NAME=i.CONSTRAINT_NAME
                        where i.TABLE_SCHEMA = '{$this->getDbSchema()}' and i.TABLE_NAME = '{$tableName}' and i.COLUMN_NAME = '{$row['Field']}'
                        and i.REFERENCED_COLUMN_NAME is not NULL;");

                if (!empty($fkResult)) {
                    while ($fkrow = $fkResult->fetch_array(MYSQLI_ASSOC)) {
                        $deleteRule = $updateRule = '';

                        if (!empty($fkrow['DELETE_RULE'])) {
                            $deleteRule = 'ON DELETE ' . $fkrow['DELETE_RULE'];
                        }

                        if (!empty($fkrow['UPDATE_RULE'])) {
                            $updateRule = 'ON UPDATE ' . $fkrow['UPDATE_RULE'];
                        }

                        $foreignStr = preg_replace('/\s+/', ' ', "{$fkrow['CONSTRAINT_NAME']} REFERENCES {$fkrow['REFERENCED_TABLE_NAME']} ({$fkrow['REFERENCED_COLUMN_NAME']}) {$deleteRule} {$updateRule}");

                        $row += ['Foreign' => $foreignStr];
                    }
                }
            }
            $columns[] = $row;
        }

        return $columns;
    }

    /**
     * Create model file name from table name and module name
     *
     * @param string $tableName
     * @param string $module
     * @return string
     * @throws \Exception
     */
    private function createModelFileName($tableName, $module)
    {
        $tbPrefix = $this->getTablePrefix();
        if (in_array(ucfirst($module), \THCFrame\Core\Core::getModuleNames())) {

            $path = MODULES_PATH . '/' . $module . '/model/basic/';
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }

            return $path . 'basic' . strtolower(str_replace($tbPrefix, '', $tableName)) . 'model.php';
        } else {
            throw new Exception\Argument($module . ' is not one of the registered application modules');
        }
    }

    /**
     * Create model class name
     *
     * @param string $tableName
     * @return string
     */
    private function createModelClassName($tableName)
    {
        $tbPrefix = $this->getTablePrefix();

        return 'Basic' . ucfirst(str_replace($tbPrefix, '', $tableName)) . 'Model';
    }

    /**
     * Create annotation comment for property from column definition
     *
     * @param array $column
     * @return string
     */
    private function createColumnAnnotations($column)
    {
        if (!empty($column)) {
            preg_match('#^([a-z]*)\(?([0-9]*)\)?#i', $column['Type'], $matches);

            $lines = [];
            switch (strtolower($column['Key'])) {
                case 'pri': {
                        $lines[] = '* @primary';
                        break;
                    }
                case 'uni': {
                        $lines[] = '* @unique';
                        break;
                    }
                case 'mul': {
                        if (isset($column['Foreign']) && !empty($column['Foreign'])) {
                            $lines[] = '* @foreign ' . $column['Foreign'];
                        } else {
                            $lines[] = '* @index';
                        }
                        break;
                    }
            }

            if (strtolower($column['Key']) == 'pri') {
                $lines[] = '* @type auto_increment';
            } else {
                $lines[] = '* @type ' . strtolower($matches[1]);
                if (!empty($matches[2])) {
                    $lines[] = '* @length ' . $matches[2];
                }
            }

            $required = false;
            if (!empty($column['Comment'])) {
                $parts = explode(';', $column['Comment']);
                foreach ($parts as $part) {
                    if (empty($part)) {
                        continue;
                    }
                    if (strpos($part, 'required') !== false) {
                        $required = true;
                    }
                    $lines[] = '* ' . $part;
                }
            }

            stripos($column['Type'], 'unsigned') !== false ? $lines[] = '* @unsigned' : '';

            if (strtolower($column['Null']) != 'no' && $required === false) {
                $lines[] = '* @null';
            }

            if ((int) $column['Default'] === 0 && strtolower($column['Null']) == 'no' && strtolower($column['Key']) != 'pri' && $required === false && empty($column['Foreign']) && in_array($matches[1], ['int', 'integer', 'tinyint', 'smallint', 'mediumint'])) {
                $lines[] = '* @default 0';
            } elseif ((int) $column['Default'] === 0 && strtolower($column['Null']) == 'no' && $required === false && in_array($matches[1], ['float', 'double', 'decimal'])) {
                $lines[] = '* @default 0.0';
            } elseif (!empty($column['Default'])) {
                $lines[] = '* @default ' . $column['Default'];
            }

            $definition = implode(PHP_EOL . '     ', $lines);
            $annotation = <<<ANNOTATION
    /**
     * @column
     * @readwrite
     {$definition}
     */
ANNOTATION;

            return $annotation;
        }
    }

    /**
     * Create model classes based on table definitions
     */
    public function createModels()
    {
        $tables = $this->getTables();

        if (!empty($tables)) {
            foreach ($tables as $table => $module) {
                Core::getLogger()->log('-------- Creating model class for ' . $table . ' --------', 'system');
                $columns = $this->getTableColumns($table);

                if (!empty($columns)) {
                    $modelWriter = new Modelwriter([
                        'filename' => $this->createModelFileName($table, $module),
                        'classname' => $this->createModelClassName($table),
                        'extends' => 'Model',
                        'namespace' => ucfirst($module) . '\Model\Basic']
                    );

                    $modelWriter->addUse('THCFrame\Model\Model');

                    foreach ($columns as $column) {
                        $an = $this->createColumnAnnotations($column);
                        $modelWriter->addProperty($column['Field'], $an);
                    }

                    $modelWriter->writeModel();
                    unset($modelWriter, $columns);
                }

                Core::getLogger()->log('-------- Model class was successfully created for table ' . $table . ' --------', 'system');
            }
        }
    }

    public function getDbIdent()
    {
        return $this->dbIdent;
    }

    public function getDbSchema()
    {
        return $this->dbSchema;
    }

    public function setDbIdent(type $dbIdent)
    {
        $this->dbIdent = $dbIdent;
        return $this;
    }

    public function setDbSchema(type $dbSchema)
    {
        $this->dbSchema = $dbSchema;
        return $this;
    }

}
