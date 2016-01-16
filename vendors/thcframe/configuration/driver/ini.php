<?php

namespace THCFrame\Configuration\Driver;

use THCFrame\Configuration as Configuration;
use THCFrame\Configuration\Exception;
use THCFrame\Core\ArrayMethods;
use THCFrame\Registry\Registry;
use THCFrame\Configuration\Model\ConfigModel;

/**
 * Ini configuration class
 */
class Ini extends Configuration\Driver
{

    /**
     * @readwrite
     * @var type 
     */
    private $_defaultConfig;

    /**
     * @readwrite
     * @var type 
     */
    private $_configuration;

    /**
     * Class constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_parse('./vendors/thcframe/configuration/default/defaultConfig.ini', true);

        switch (strtolower($this->getEnv())) {
            case 'dev': {
                    $this->_parse('./application/configuration/config_dev.ini');
                    break;
                }
            case 'qa': {
                    $this->_parse('./application/configuration/config_qa.ini');
                    break;
                }
            case 'live': {
                    $this->_parse('./application/configuration/config_live.ini');
                    break;
                }
        }

        $this->_configuration = ArrayMethods::toObject($this->_mergeConfiguration());
    }

    /**
     * Method used to merge configuration of specific environment into 
     * default configuration
     * 
     * @return type
     */
    protected function _mergeConfiguration()
    {
        return array_replace_recursive($this->_defaultConfig, $this->_configuration);
    }

    /**
     * The _pair() method deconstructs the dot notation, used in the configuration file’s keys, 
     * into an associative array hierarchy. If the $key variable contains a dot character (.),
     * the first part will be sliced off, used to create a new array, and 
     * assigned the value of another call to _pair().
     * 
     * @param array $config
     * @param type $key
     * @param mixed $value
     * @return array
     */
    protected function _pair($config, $key, $value)
    {
        if (strstr($key, '.')) {
            $parts = explode('.', $key, 2);

            if (empty($config[$parts[0]])) {
                $config[$parts[0]] = array();
            }

            $config[$parts[0]] = $this->_pair($config[$parts[0]], $parts[1], $value);
        } else {
            $config[$key] = $value;
        }

        return $config;
    }

    /**
     * Method checks to see that the $path argument is not empty, 
     * throwing a ConfigurationExceptionArgument exception if it is. 
     * Next, it checks to see if the requested configuration 
     * file has not already been parsed, and if it has it jumps right to where it
     * returns the configuration.
     * 
     * Method loop through the associative array returned by parse_ini_string, 
     * generating the correct hierarchy (using the _pair() method), 
     * finally converting the associative array to an object and caching/returning the configuration
     * file data.
     * 
     * @param string $path
     * @return object
     * @throws Exception\Argument
     * @throws Exception\Syntax
     */
    protected function _parse($path, $isDefault = false)
    {
        if (empty($path) || !file_exists($path)) {
            throw new Exception\Argument('Path argument is not valid');
        }

        if (!isset($this->_configuration)) {
            $config = array();

            ob_start();
            include($path);
            $string = ob_get_contents();
            ob_end_clean();

            $pairs = parse_ini_string($string);

            if ($pairs == false) {
                throw new Exception\Syntax('Could not parse configuration file');
            }

            foreach ($pairs as $key => $value) {
                $config = $this->_pair($config, $key, $value);
            }

            if ($isDefault === true) {
                $this->_defaultConfig = $config;
            } else {
                $this->_configuration = $config;
            }
        }
    }

    /**
     * Extends configuration loaded from config file for configuration loaded
     * form database
     */
    public function extendForDbConfig()
    {
        $ca = ConfigModel::all(array(), array('xkey', 'value'));

        if ($ca !== null) {
            if ($this->_configuration instanceof \stdClass) {
                foreach ($ca as $key => $value) {
                    $this->_configuration->{$value->getXkey()} = $value->getValue();
                }
            } elseif (is_array($this->_configuration)) {
                foreach ($ca as $key => $value) {
                    $this->_configuration[$value->getXkey()] = $value->getValue();
                }
                $this->_configuration = ArrayMethods::toObject($this->_configuration);
            } else {
                throw new Exception\Syntax('Error while loading configuration from database');
            }
        }
    }

    /**
     * 
     * @return type
     */
    public function getConfiguration()
    {
        return $this->_configuration;
    }

}
