<?php

namespace THCFrame\Logger;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;

/**
 * Logger factory class
 */
class Logger extends Base
{

    /**
     * @readwrite
     */
    protected $_type;

    /**
     * @readwrite
     */
    protected $_options;

    /**
     *
     * @param string $method
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
     * @return Driver\Db|Driver\Email|Driver\File
     * @throws Exception\Argument
     */
    public function initialize()
    {
        Event::fire('framework.logger.initialize.before', [$this->_type, $this->_options]);

        if (!$this->_type) {
            throw new Exception\Argument('Error in configuration file');
        }

        Event::fire('framework.logger.initialize.after', [$this->_type, $this->_options]);

        switch ($this->_type) {
            case 'file':
                return new Driver\File([
                    'path' => 'application' . DIRECTORY_SEPARATOR . 'logs',
                ]);
            case 'email':
                return new Driver\Email();
            case 'db':
                return new Driver\Db();
            default:
                throw new Exception\Argument('Invalid logger type');
        }
    }

}
