<?php

namespace THCFrame\Database;

use THCFrame\Configuration\Driver;
use THCFrame\Core\Base;
use THCFrame\Database\Connector\Mysql;
use THCFrame\Events\Events as Event;

/**
 * Factory class returns a Database\Connector subclass.
 * Connectors are the classes that do the actual interfacing with the
 * specific database engine. They execute queries and return data
 */
class Database extends Base
{

    /**
     * @readwrite
     */
    protected $type;

    /**
     * @readwrite
     */
    protected $options;

    /**
     *
     * @param type $method
     * @return Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Factory method
     * It accepts initialization options and selects the type of returned object,
     * based on the internal $_type property.
     *
     * @param Driver $configuration
     * @return ConnectionHandler
     * @throws Exception\Argument
     * @throws Exception\Connector
     */
    public function initialize($configuration)
    {
        Event::fire('framework.database.initialize.before', []);

        $databases = $configuration->database;
        $conHandler = new ConnectionHandler();

        if (!empty($databases)) {
            foreach ($databases as $dbIdent) {
                if (!empty($dbIdent) && !empty($dbIdent->type)) {
                    $type = $dbIdent->type;
                    $options = (array)$dbIdent;
                } else {
                    throw new Exception\Argument('Error in configuration file');
                }

                try {
                    $connector = $this->createConnector($type, $options);
                    $conHandler->add($dbIdent->id, $connector);
                    $connector->connect();
                } catch (Exception $exc) {
                    throw new Exception\Connector($exc->getMessage());
                }

                Event::fire('framework.database.initialize.after', [$type, $options]);
            }
        }

        return $conHandler;
    }

    /**
     *
     * @param array $options
     * @return Connector\Mysql
     * @throws Exception\Argument
     * @throws Exception\Service
     */
    public function initializeDirectly($options)
    {
        if (!empty($options['type'])) {
            $type = $options['type'];
            $options = (array)$options;
        } else {
            throw new Exception\Argument('Error in configuration');
        }

        $connector = $this->createConnector($type, $options);
        $connector->connect();

        return $connector;
    }

    /**
     *
     * @param string $type $type
     * @param array $options
     * @return Mysql
     * @throws Exception\Argument
     */
    private function createConnector($type = 'mysql', $options = [])
    {
        if (empty($options)) {
            throw new Exception\Argument('Invalid database options');
        }

        switch ($type) {
            case 'mysql':
                {
                    return new Connector\Mysql($options);
                }
            default:
                {
                    throw new Exception\Argument('Invalid database type');
                }
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

}
