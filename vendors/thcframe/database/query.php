<?php

namespace THCFrame\Database;

use THCFrame\Core\Base;
use THCFrame\Core\ArrayMethods;
use THCFrame\Core\StringMethods;
use THCFrame\Database\Exception as Exception;
use THCFrame\Core\Core;
use THCFrame\Registry\Registry;

/**
 * Query class for OO query creating
 */
class Query extends Base
{

    /**
     * @readwrite
     */
    protected $_connector;

    /**
     * @read
     */
    protected $_from;

    /**
     * @read
     */
    protected $_alias;

    /**
     * @read
     */
    protected $_fields;

    /**
     * @read
     */
    protected $_limit;

    /**
     * @read
     */
    protected $_offset;

    /**
     * @read
     */
    protected $_order = [];

    /**
     * @read
     */
    protected $_groupby;

    /**
     * @read
     */
    protected $_join = [];

    /**
     * @read
     */
    protected $_where = [];

    /**
     * @read
     */
    protected $_wheresql;

    /**
     * @read
     */
    protected $_having = [];

    /**
     *
     * @param type $method
     * @return \THCFrame\Database\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     *
     * @param type $error
     * @param type $sql
     */
    protected function _logError($error, $sql)
    {
        $errMessage = 'There was an error in the query {error} - SQL: {query}';
        Core::getLogger()->error($errMessage, ['error' => $error, 'query' => $sql]);
    }

    /**
     *
     * @param mixed $value
     * @return string
     * @throws Exception\Connector
     */
    protected function _quote($value)
    {
        if (is_null($value)) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        $connector = $this->getConnector();
        !is_array($value) ? $value = trim($value): '';

        if (is_numeric($value)) {
            $escaped = $connector->escape($value);
            return $escaped;
        }

        if (is_string($value)) {
            $escaped = $connector->escape($value);
            return "'{$escaped}'";
        }

        if (is_array($value)) {
            $buffer = [];

            foreach ($value as $i) {
                array_push($buffer, $this->_quote($i));
            }

            $buffer = join(', ', $buffer);
            return "({$buffer})";
        }

        return $connector->escape($value);
    }

    /**
     *
     * @return string
     */
    protected function buildSelect()
    {
        $fields = [];
        $where = $order = $limit = $join = '';
        $template = 'SELECT %s FROM %s %s %s %s %s %s %s %s';

        foreach ($this->fields as $table => $_fields) {
            foreach ($_fields as $field => $alias) {
                if (is_string($field)) {
                    $fields[] = "{$field} AS {$alias}";
                } else {
                    $fields[] = $alias;
                }
            }
        }

        $joinedFields = join(', ', $fields);

        $_join = $this->join;
        if (!empty($_join)) {
            $join = join(' ', $_join);
        }

        $whereConditions = '';
        if (!empty($this->_where)) {
            $whereConditions = join(' AND ', $this->_where);
        } elseif ($this->_wheresql != '') {
            $whereConditions = $this->_wheresql;
        }

        if ($whereConditions != '') {
            $where = "WHERE {$whereConditions}";
        }

        $_groupBy = $this->groupby;
        $groupBy = $having = '';
        if (!empty($_groupBy)) {
            $groupBy = "GROUP BY {$_groupBy}";

            $_having = $this->having;
            if (!empty($_having)) {
                $joinedHaving = join(' AND ', $_having);
                $having = "HAVING {$joinedHaving}";
            }
        }

        $_order = $this->order;
        if (!empty($_order)) {
            $joindeOrder = join(', ', $_order);
            $order = "ORDER BY {$joindeOrder}";
        }

        $_limit = $this->limit;
        if (!empty($_limit)) {
            $_offset = $this->offset;

            if ($_offset) {
                $limit = "LIMIT {$_offset}, {$_limit}";
            } else {
                $limit = "LIMIT {$_limit}";
            }
        }

        $input = sprintf($template, $joinedFields, $this->from, $this->alias, $join, $where, $groupBy, $having, $order, $limit);
        $output = mb_ereg_replace('\s+', ' ', $input);

        if(Registry::get('configuration')->profiler->logSql == 1){
            Core::getLogger()->debug('{sql}', ['sql' => $output]);
        }

        return $output;
    }

    /**
     *
     * @param array $data
     * @return string
     * @throws Exception\Connector
     */
    protected function _buildInsert($data)
    {
        $fields = [];
        $values = [];
        $template = 'INSERT INTO `%s` (`%s`) VALUES (%s)';

        foreach ($data as $field => $value) {
            $fields[] = $field;
            $values[] = $this->_quote($value);
        }

        $fields = join('`, `', $fields);
        $values = join(', ', $values);

        $input = sprintf($template, $this->from, $fields, $values);
        $output = mb_ereg_replace('\s+', ' ', $input);

        if(Registry::get('configuration')->profiler->logSql == 1){
            Core::getLogger()->debug('{sql}', ['sql' => $output]);
        }

        return $output;
    }

    /**
     *
     * @param array $data
     * @return string
     * @throws Exception\Connector
     */
    protected function _buildUpdate($data)
    {
        $parts = [];
        $where = $limit = '';
        $template = 'UPDATE %s SET %s %s %s';

        foreach ($data as $field => $value) {
            $parts[] = "{$field} = " . $this->_quote($value);
        }

        $parts = join(', ', $parts);

        $whereConditions = '';
        if (!empty($this->_where)) {
            $whereConditions = join(' AND ', $this->_where);
        } elseif ($this->_wheresql != '') {
            $whereConditions = $this->_wheresql;
        }

        if ($whereConditions != '') {
            $where = "WHERE {$whereConditions}";
        }

        $_limit = $this->limit;
        if (!empty($_limit)) {
            $_offset = $this->offset;
            $limit = "LIMIT {$_limit} {$_offset}";
        }

        $input = sprintf($template, $this->from, $parts, $where, $limit);
        $output = mb_ereg_replace('\s+', ' ', $input);

        if(Registry::get('configuration')->profiler->logSql == 1){
            Core::getLogger()->debug('{sql}', ['sql' => $output]);
        }

        return $output;
    }

    /**
     *
     * @return string
     */
    protected function _buildDelete()
    {
        $where = $limit = '';
        $template = 'DELETE FROM %s %s %s';

        $whereConditions = '';
        if (!empty($this->_where)) {
            $whereConditions = join(' AND ', $this->_where);
        } elseif ($this->_wheresql != '') {
            $whereConditions = $this->_wheresql;
        }

        if ($whereConditions != '') {
            $where = "WHERE {$whereConditions}";
        }

        $_limit = $this->limit;
        if (!empty($_limit)) {
            $_offset = $this->offset;
            $limit = "LIMIT {$_limit} {$_offset}";
        }

        $input = sprintf($template, $this->from, $where, $limit);
        $output = mb_ereg_replace('\s+', ' ', $input);

        if(Registry::get('configuration')->profiler->logSql == 1){
            Core::getLogger()->debug('{sql}', ['sql' => $output]);
        }

        return $output;
    }

    /**
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

            $database = Registry::get('database')->get($dbIdent);

            if (!$database) {
                throw new Exception\Connector('No connector availible');
            }

            $this->_connector = $database;
        }

        return $this->_connector;
    }

    /**
     *
     * @return string
     */
    public function getTableAlias()
    {
        return $this->_alias;
    }

    /**
     *
     * @param string $alias
     * @return Query
     * @throws \THCFrame\Database\Exception
     */
    public function setTableAlias($alias)
    {
        if (StringMethods::match($alias, '#^([a-z_-]*)$#')) {
            $this->_alias = $alias;
        } else {
            throw new Exception('Table alias is not valid alias');
        }

        return $this;
    }

    /**
     *
     * @param string $alias
     * @return Query
     * @throws \THCFrame\Database\Exception
     */
    public function setAlias($alias)
    {
        $this->setTableAlias($alias);

        return $this;
    }

    /**
     *
     * @param array $data
     * @return int
     * @throws Exception\Connector
     * @throws Exception\Sql
     */
    public function save($data)
    {
        $isInsert = count($this->_where) == 0;

        if ($isInsert) {
            $sql = $this->_buildInsert($data);
        } else {
            $sql = $this->_buildUpdate($data);
        }

        $result = $this->connector->execute($sql);

        if ($result === false) {
            $err = $this->connector->getLastError();
            $this->_logError($err, $sql);

            if (ENV == Core::ENV_DEV) {
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $err));
            } else {
                throw new Exception\Sql('There was an error');
            }
        }

        if ($isInsert) {
            return $this->connector->getLastInsertId();
        }

        return 0;
    }

    /**
     *
     * @return int
     * @throws Exception\Sql
     */
    public function delete()
    {
        $sql = $this->_buildDelete();
        $result = $this->connector->execute($sql);

        if ($result === false) {
            $err = $this->connector->getLastError();
            $this->_logError($err, $sql);

            if (ENV == Core::ENV_DEV) {
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $err));
            } else {
                throw new Exception\Sql('There was an error');
            }
        }

        return $this->connector->getAffectedRows();
    }

    /**
     *
     * @param array $data
     * @return int
     * @throws Exception\Connector
     * @throws Exception\Sql
     */
    public function update($data)
    {
        $sql = $this->_buildUpdate($data);
        $result = $this->connector->execute($sql);

        if ($result === false) {
            $err = $this->connector->getLastError();
            $this->_logError($err, $sql);

            if (ENV == Core::ENV_DEV) {
                throw new Exception\Sql(sprintf('There was an error with your SQL query: %s', $err));
            } else {
                throw new Exception\Sql('There was an error');
            }
        }

        return $this->connector->getAffectedRows();
    }

    /**
     *
     * @param string $from
     * @param array $fields
     * @return Query
     * @throws Exception\Argument
     */
    public function from($from, $fields = ['*'])
    {
        if (empty($from)) {
            throw new Exception\Argument('Invalid argument');
        }

        $this->_from = $from;

        if ($fields) {
            $this->_fields[$from] = $fields;
        }

        return $this;
    }

    /**
     *
     * @param string $join
     * @param string $on
     * @param null|string $alias
     * @param array $fields
     * @return Query
     * @throws Exception\Argument
     */
    public function join($join, $on, $alias = null, $fields = ['*'])
    {
        if (empty($join) || empty($on)) {
            throw new Exception\Argument('Invalid argument');
        }

        if (NULL !== $alias) {
            $this->_fields += [$alias => $fields];
            $this->_join[] = "JOIN {$join} {$alias} ON {$on}";
        } else {
            $this->_fields += [$join => $fields];
            $this->_join[] = "JOIN {$join} ON {$on}";
        }

        return $this;
    }

    /**
     *
     * @param string $join
     * @param string $on
     * @param null|string $alias
     * @param array $fields
     * @return Query
     * @throws Exception\Argument
     */
    public function leftjoin($join, $on, $alias = null, $fields = ['*'])
    {
        if (empty($join) || empty($on)) {
            throw new Exception\Argument('Invalid argument');
        }

        if (NULL !== $alias) {
            $this->_fields += [$alias => $fields];
            $this->_join[] = "LEFT JOIN {$join} {$alias} ON {$on}";
        } else {
            $this->_fields += [$join => $fields];
            $this->_join[] = "LEFT JOIN {$join} ON {$on}";
        }

        return $this;
    }

    /**
     *
     * @param string $join
     * @param string $on
     * @param null|string $alias
     * @param array $fields
     * @return Query
     * @throws Exception\Argument
     */
    public function rightjoin($join, $on, $alias = null, $fields = ['*'])
    {
        if (empty($join) || empty($on)) {
            throw new Exception\Argument('Invalid argument');
        }

        if (NULL !== $alias) {
            $this->_fields += [$alias => $fields];
            $this->_join[] = "RIGHT JOIN {$join} {$alias} ON {$on}";
        } else {
            $this->_fields += [$join => $fields];
            $this->_join[] = "RIGHT JOIN {$join} ON {$on}";
        }

        return $this;
    }

    /**
     *
     * @param int $limit
     * @param int $page
     * @return Query
     * @throws Exception\Argument
     * @throws Exception\Connector
     */
    public function limit($limit, $page = 1)
    {
        if (empty($limit)) {
            throw new Exception\Argument('Invalid argument');
        }

        $this->_limit = $this->_quote($limit);
        $page = (int) $this->_quote($page);

        if ($page - 1 <= 0) {
            $this->_offset = 0;
        } else {
            $this->_offset = $limit * ($page - 1);
        }

        return $this;
    }

    /**
     *
     * @param string $order
     * @param string $direction
     * @return Query
     * @throws Exception\Argument
     */
    public function order($order, $direction = 'asc')
    {
        if (empty($order)) {
            throw new Exception\Argument('Invalid argument');
        }

        $this->_order[] = $order . ' ' . $direction;

        return $this;
    }

    /**
     *
     * @return Query
     * @throws Exception\Argument
     * @throws Exception\Connector
     * @throws Exception\Sql
     */
    public function where()
    {
        if ($this->_wheresql != '') {
            throw new Exception\Sql('You can use only one of the where methods');
        }

        $arguments = func_get_args();

        if (count($arguments) < 1) {
            throw new Exception\Argument('Invalid argument');
        }

        $arguments[0] = mb_ereg_replace('\?', '%s', $arguments[0]);

        foreach (array_slice($arguments, 1, null, true) as $i => $parameter) {
            $arguments[$i] = $this->_quote($arguments[$i]);
        }

        $this->_where[] = call_user_func_array('sprintf', $arguments);

        return $this;
    }

    /**
     *
     * @return Query
     * @throws Exception\Argument
     * @throws Exception\Connector
     * @throws Exception\Sql
     */
    public function wheresql()
    {
        if (!empty($this->_where)) {
            throw new Exception\Sql('You can use only one of the where methods');
        }

        $arguments = func_get_args();

        if (count($arguments) < 1) {
            throw new Exception\Argument('Invalid argument');
        }

        $connector = $this->getConnector();
        $arguments[0] = mb_ereg_replace('\?', '%s', $arguments[0]);

        foreach (array_slice($arguments, 1, null, true) as $i => $parameter) {
            $arguments[$i] = $connector->escape($arguments[$i]);
        }

        $this->_wheresql = call_user_func_array('sprintf', $arguments);

        return $this;
    }

    /**
     *
     * @return Query
     * @throws Exception\Argument
     * @throws Exception\Connector
     */
    public function having()
    {
        $arguments = func_get_args();

        if (count($arguments) < 1) {
            throw new Exception\Argument('Invalid argument');
        }

        $arguments[0] = mb_ereg_replace('\?', '%s', $arguments[0]);

        foreach (array_slice($arguments, 1, null, true) as $i => $parameter) {
            $arguments[$i] = $this->_quote($arguments[$i]);
        }

        $this->_having[] = call_user_func_array('sprintf', $arguments);

        return $this;
    }

    /**
     *
     * @param string $field
     * @return Query
     * @throws Exception\Argument
     */
    public function groupby($field)
    {
        if (empty($field)) {
            throw new Exception\Argument('Invalid argument');
        }

        $this->_groupby = $field;

        return $this;
    }

    /**
     *
     * @return mixed
     * @throws Exception\Argument
     * @throws Exception\Connector
     */
    public function first()
    {
        $limit = $this->_limit;
        $offset = $this->_offset;

        $this->limit(1);

        $all = $this->all();
        $first = ArrayMethods::first($all);

        if ($limit) {
            $this->_limit = $limit;
        }
        if ($offset) {
            $this->_offset = $offset;
        }

        return $first;
    }

    /**
     *
     * @return int
     * @throws Exception\Argument
     * @throws Exception\Connector
     */
    public function count()
    {
        $limit = $this->limit;
        $offset = $this->offset;
        $fields = $this->fields;

        $this->_fields = [$this->from => ['COUNT(1)' => 'cnt']];

        $this->limit(1);
        $row = $this->first();

        $this->_fields = $fields;

        if ($fields) {
            $this->_fields = $fields;
        }
        if ($limit) {
            $this->_limit = $limit;
        }
        if ($offset) {
            $this->_offset = $offset;
        }

        return $row['cnt'];
    }

    /**
     *
     * @return string
     */
    public function assemble()
    {
        $sql = $this->buildSelect();
        return (string) $sql;
    }

}
