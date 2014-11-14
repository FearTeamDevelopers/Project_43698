<?php

namespace THCFrame\Profiler;

use THCFrame\Registry\Registry;
use THCFrame\Events\Events as Event;
use THCFrame\Request\RequestMethods;

/**
 * Application and database profiler class
 */
class Profiler
{

    /**
     * Profiles
     * 
     * @var Profiler
     */
    private static $_profiles = array();

    /**
     * Database profiler informations
     * 
     * @var array
     */
    private static $_dbProfiles = array();

    /**
     * Application profiler informations
     * 
     * @var array
     */
    private static $_body;

    /**
     * Flag if profiler is active
     * 
     * @var boolean
     */
    private static $_active = null;

    /**
     * Last database profiler indentifier
     * 
     * @var string
     */
    private static $_dbLastIdentifier;
    
    /**
     * Last database profiler indentifier
     * 
     * @var string
     */
    private static $_dbLastQueryIdentifier;

    /**
     * 
     */
    private function __clone()
    {
        
    }

    /**
     * 
     */
    private function __wakeup()
    {
        
    }

    /**
     * Object constructor
     */
    private function __construct()
    {
        Event::fire('framework.profiler.construct');
    }

    /**
     * Convert unit for better readyability
     * 
     * @param mixed $size
     * @return mixed
     */
    private static function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     * Check if profiler should be active or not
     * 
     * @return boolean
     */
    public static function isActive()
    {
        Event::fire('framework.profiler.check');

        if (self::$_active === null) {
            $configuration = Registry::get('configuration');
            $active = (bool) $configuration->profiler->active;
        } else {
            $active = self::$_active;
        }

        if ($active === true) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Start application profiling
     * 
     * @param string $identifier
     */
    public static function start($identifier = 'CORE')
    {
        if (self::isActive()) {
            self::dbStart($identifier);
            self::$_profiles[$identifier]['startTime'] = microtime(true);
            self::$_profiles[$identifier]['startMemoryPeakUsage'] = self::convert(memory_get_peak_usage());
            self::$_profiles[$identifier]['startMomoryUsage'] = self::convert(memory_get_usage());
        }
    }

    /**
     * End of application profiling
     * 
     * @param string $identifier
     */
    public static function stop($identifier = 'CORE')
    {
        if (self::isActive()) {
            self::$_profiles[$identifier]['requestUri'] = RequestMethods::server('REQUEST_URI');
            self::$_profiles[$identifier]['totalTime'] = round(microtime(true) - self::$_profiles[$identifier]['startTime'], 8);
            self::$_profiles[$identifier]['endMemoryPeakUsage'] = self::convert(memory_get_peak_usage());
            self::$_profiles[$identifier]['endMomoryUsage'] = self::convert(memory_get_usage());
            self::$_profiles[$identifier]['dbProfiles'] = self::$_dbProfiles[$identifier];
            self::$_profiles[$identifier]['sessionArr'] = $_SESSION;
            self::$_profiles[$identifier]['postArr'] = $_POST;
            self::$_profiles[$identifier]['getArr'] = $_GET;
            
            self::dbStop($identifier);
            
            file_put_contents(APP_PATH . '/application/logs/profiler.log', serialize(self::$_profiles));
        }
    }

    /**
     * Start of database profiling
     */
    public static function dbStart($identifier = 'CORE')
    {
        if (self::isActive()) {
            self::$_dbProfiles[$identifier] = array();
            self::$_dbLastIdentifier = $identifier;
        }
    }
    
    /**
     * Stop of database profiling
     */
    public static function dbStop($identifier = 'CORE')
    {
        if (self::isActive()) {
            unset(self::$_dbProfiles[$identifier]);
            self::$_dbLastIdentifier = 'CORE';
        }
    }

    /**
     * Start of database query profiling
     * 
     * @param string $query
     * @return type
     */
    public static function dbQueryStart($query)
    {
        if (self::isActive()) {
            self::$_dbLastQueryIdentifier = microtime();
            
            self::$_dbProfiles[self::$_dbLastIdentifier][self::$_dbLastQueryIdentifier]['startTime'] = microtime(true);
            self::$_dbProfiles[self::$_dbLastIdentifier][self::$_dbLastQueryIdentifier]['query'] = $query;
        }
    }

    /**
     * End of database query profiling
     * 
     * @param mixed $totalRows
     * @return type
     */
    public static function dbQueryStop($totalRows)
    {
        if (self::isActive()) {
            $startTime = self::$_dbProfiles[self::$_dbLastIdentifier][self::$_dbLastQueryIdentifier]['startTime'];
            self::$_dbProfiles[self::$_dbLastIdentifier][self::$_dbLastQueryIdentifier]['execTime'] = round(microtime(true) - $startTime, 8);
            self::$_dbProfiles[self::$_dbLastIdentifier][self::$_dbLastQueryIdentifier]['totalRows'] = $totalRows;
            self::$_dbProfiles[self::$_dbLastIdentifier][self::$_dbLastQueryIdentifier]['backTrace'] = debug_backtrace();
        }
    }
    

    /**
     * Save informations into file and return it
     */
    public static function display()
    {
        if (self::isActive()) {
            $fileContent = file_get_contents(APP_PATH . '/application/logs/profiler.log');
            $profiles = unserialize($fileContent);
                

            $str = '<link href="/public/css/plugins/profiler.min.css" media="screen" rel="stylesheet" type="text/css" /><div id="profiler">';

            foreach ($profiles as $ident => $profile) {
                $str .= "<div class='profiler-basic'>"
                        . "<span title='Profile Identifier'>{$ident}</span>"
                        . "<span title='Request URI'>{$profile['requestUri']}</span>"
                        . "<span title='Execution time [s]'>{$profile['totalTime']}</span>"
                        . "<span title='Memory peak usage'>{$profile['endMemoryPeakUsage']}</span>"
                        . "<span title='Memory usage'>{$profile['endMomoryUsage']}</span>"
                        . '<span title="SQL Query"><a href="#" class="profiler-show-query" value="'.$ident.'">SQL Query:</a> ' . count($profile['dbProfiles']) . '</span>'
                        . '<span><a href="#" class="profiler-show-globalvar" value="'.$ident.'">Global variables</a></span></div>';
                $str .= '<div class="profiler-query" id="'.$ident.'_db">'
                        . '<table><tr style="font-weight:bold; border-top:1px solid black;" class="query-header">'
                        . '<td colspan=5>Query</td><td>Execution time [s]</td><td>Returned rows</td><td colspan=6>Backtrace</td></tr>';

                foreach ($profile['dbProfiles'] as $key => $value) {
                    $str .= '<tr>';
                    $str .= "<td colspan=5 width='40%'>{$value['query']}</td>";
                    $str .= "<td>{$value['execTime']}</td>";
                    $str .= "<td>{$value['totalRows']}</td>";
                    $str .= "<td colspan=6 class=\"backtrace\"><div>";

                    foreach ($value['backTrace'] as $key => $trace) {
                        isset($trace['file']) ? $file = $trace['file'] : $file = '';
                        isset($trace['line']) ? $line = $trace['line'] : $line = '';
                        isset($trace['class']) ? $class = $trace['class'] : $class = '';
                        $str .= $key . ' ' . $file . ':' . $line . ':' . $class . ':' . $trace['function'] . "<br/>";
                    }
                    $str .= "</div></td></tr>";
                }

                $str .= '</table></div>';
                $str .= '<div class="profiler-globalvar" id="'.$ident.'_vars"><table>';
                $str .= '<tr><td colspan=2>SESSION</td></tr>';

                foreach ($profile['sessionArr'] as $key => $value) {
                    if (is_array($value)) {
                        $str .= '<tr><td>' . $key . '</td><td>' . $value[0] . '</td></tr>';
                    } else {
                        $str .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                    }
                }

                $str .= '</table><table>';
                $str .= '<tr><td colspan=2>POST</td></tr>';

                foreach ($profile['postArr'] as $key => $value) {
                    if (is_array($value)) {
                        $str .= '<tr><td>' . $key . '</td><td>' . $value[0] . '</td></tr>';
                    } else {
                        $str .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                    }
                }

                $str .= '</table><table>';
                $str .= '<tr><td colspan=2>GET</td></tr>';

                foreach ($profile['getArr'] as $key => $value) {
                    if (is_array($value)) {
                        $str .= '<tr><td>' . $key . '</td><td>' . $value[0] . '</td></tr>';
                    } else {
                        $str .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                    }
                }

                $str .= '</table></div>';
            }

            $str .= '</div><script type="text/javascript" src="/public/js/plugins/profiler.min.js"></script>';

            return $str;
        } else {
            return '';
        }
    }

}
