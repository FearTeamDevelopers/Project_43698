<?php

namespace THCFrame\Database;

use THCFrame\Core\Base;
use THCFrame\Database\Exception;

/**
 *
 */
class ConnectionHandler extends Base
{

    /** @var Connector[] */
    private $connectors = [];

    /**
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
    }

    /**
     *
     * @param string $id
     * @param Connector $connector
     * @return ConnectionHandler
     * @throws Exception\Argument
     */
    public function add($id, $connector)
    {
        if ($connector instanceof Connector) {
            $id = strtolower(trim($id));
            $this->connectors[$id] = $connector;
        } else {
            throw new Exception\Argument(sprintf('%s is not valid connector', $id));
        }

        return $this;
    }

    /**
     *
     * @param string $id
     * @return Connector
     * @throws Exception\Argument
     */
    public function get($id = 'main')
    {
        if (array_key_exists($id, $this->connectors)) {
            $id = strtolower(trim($id));
            return $this->connectors[$id];
        } else {
            throw new Exception\Argument(sprintf('%s is not registred connector', $id));
        }
    }

    /**
     *
     * @param string $id
     * @return ConnectionHandler
     */
    public function erase($id)
    {
        $id = strtolower(trim($id));

        if (array_key_exists($id, $this->connectors)) {
            unset($this->connectors[$id]);
        }

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getIdentifications()
    {
        if (!empty($this->connectors)) {
            return array_keys($this->connectors);
        } else {
            return [];
        }
    }

    /**
     *
     * @param string $id
     * @return ConnectionHandler
     */
    public function disconnectById($id)
    {
        $id = strtolower(trim($id));

        if (array_key_exists($id, $this->connectors)) {
            $this->connectors[$id]->disconnect();
        }

        return $this;
    }

    /**
     *
     */
    public function disconnectAll()
    {
        foreach ($this->connectors as $connector) {
            $connector->disconnect();
        }
    }

}
