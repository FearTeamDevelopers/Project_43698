<?php

namespace THCFrame\Logger;

use THCFrame\Core\Base;
use THCFrame\Logger\Exception;
use THCFrame\Logger\LoggerInterface;

/**
 * Factory allows many different kinds of configuration driver classes to be used,
 * we need a way to share code across all driver classes.
 */
abstract class Driver extends Base implements LoggerInterface
{

    const EMERGENCY = 'emergency';
    const ALERT = 'alert';
    const CRITICAL = 'critical';
    const ERROR = 'error';
    const WARNING = 'warning';
    const NOTICE = 'notice';
    const INFO = 'info';
    const DEBUG = 'debug';

    /**
     * @return \THCFrame\Logger\Driver
     */
    public function initialize()
    {
        return $this;
    }

    /**
     *
     * @param type $method
     * @return \THCFrame\Session\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     *
     * @param type $message
     * @param array $context
     * @return type
     */
    protected function interpolate($message, array $context = array())
    {
        // vytvoří nahrazovací pole se závorkami okolo kontextových klíčů
        $replace = array();
        if (!empty($context)) {
            foreach ($context as $key => $val) {
                $replace['{' . $key . '}'] = $val;
            }
            // interpoluje nahrazovací hodnoty do zprávy a vrátí je
            return strtr($message, $replace);
        }
        return $message;
    }

}
