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
    private static $instance = null;

    /**
     * Profiles
     *
     * @var array
     */
    private $profiles = [];

    /**
     * Database profiler informations
     *
     * @var array
     */
    private $dbProfiles = [];

    /**
     * Flag if profiler is active
     *
     * @var boolean
     */
    private $active;

    /**
     * Last database profiler indentifier
     *
     * @var string
     */
    private $dbLastIdentifier;

    /**
     * Last database profiler indentifier
     *
     * @var string
     */
    private $dbLastQueryIdentifier;

    /**
     * Arry for profiling and debug messages
     *
     * @var array $messages
     */
    private $messages = [];

    /**
     * File for storing profiler result
     *
     * @var string $profilerFile
     */
    private $profilerFile = APP_PATH . '/application/logs/profiler.log';

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

    }

    /**
     * Convert unit for better readyability
     *
     * @param mixed $size
     * @return mixed
     */
    private function _convert($size)
    {
        $unit = ['b', 'kb', 'mb', 'gb'];
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }

    /**
     *
     * @return type
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Check if profiler should be active or not
     *
     * @return boolean
     */
    private function isActive()
    {
        if ($this->active === null) {
            $configuration = Registry::get('configuration');
            $this->active = $configuration->profiler->active;
        }
        return $this->active;
    }

    /**
     * Start application profiling
     *
     * @param string $identifier
     */
    public function start($identifier = 'CORE')
    {
        if ($this->isActive()) {
            Event::fire('framework.profiler.start.before', [$identifier]);

            $this->dbStart($identifier);
            $this->profiles[$identifier]['startMemoryPeakUsage'] = $this->_convert(memory_get_peak_usage());
            $this->profiles[$identifier]['startMomoryUsage'] = $this->_convert(memory_get_usage());

            Event::fire('framework.profiler.start.after', [$identifier]);
        }
    }

    /**
     * End of application profiling
     *
     * @param string $identifier
     */
    public function stop($identifier = 'CORE')
    {
        if ($this->isActive()) {
            Event::fire('framework.profiler.stop.before', [$identifier]);

            $this->profiles[$identifier]['requestUri'] = RequestMethods::server('REQUEST_URI');
            $this->profiles[$identifier]['endMemoryPeakUsage'] = $this->_convert(memory_get_peak_usage());
            $this->profiles[$identifier]['endMomoryUsage'] = $this->_convert(memory_get_usage());

            $this->profiles[$identifier]['dbProfiles'] = $this->dbProfiles[$identifier];
            $this->dbStop($identifier);

            $this->profiles[$identifier]['data'] = ['session' => $_SESSION, 'post' => $_POST, 'get' => $_GET];

            Event::fire('framework.profiler.stop.after', [$identifier]);

            $this->profiles[$identifier]['messages'] = $this->getMessages();
            $this->profiles[$identifier]['totalTime'] = round(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"], 3);
            $this->saveResult();
        }
    }

    /**
     * Save profiler result as json to file
     */
    public function saveResult()
    {
        $content = file_get_contents($this->profilerFile);

        if (mb_strlen($content) < 10) {
            file_put_contents($this->profilerFile, json_encode($this->profiles));
        } else {
            $result = $this->mergeResults(json_decode($content, true), $this->profiles);
            file_put_contents($this->profilerFile, json_encode($result));
        }
    }

    /**
     * Merge two results
     *
     * @param type $prevResult
     * @param type $currentResult
     * @return type
     */
    public function mergeResults($prevResult, $currentResult)
    {
        $merged = [];

        foreach ($currentResult as $ident => $data) {
            $merged[$ident]['requestUri'] = $prevResult[$ident]['requestUri'] . ' -> ' . $data['requestUri'];
            $merged[$ident]['endMemoryPeakUsage'] = max($prevResult[$ident]['endMemoryPeakUsage'], $data['endMemoryPeakUsage']);
            $merged[$ident]['endMomoryUsage'] = max($prevResult[$ident]['endMomoryUsage'], $data['endMomoryUsage']);
            $merged[$ident]['dbProfiles'] = array_merge($prevResult[$ident]['dbProfiles'], $data['dbProfiles']);
            $merged[$ident]['data'] = [
                'session' => array_merge($prevResult[$ident]['data']['session'], $data['data']['session']),
                'post' => array_merge_recursive($prevResult[$ident]['data']['post'], $data['data']['post']),
                'get' => array_merge_recursive($prevResult[$ident]['data']['get'], $data['data']['get'])
            ];

            $merged[$ident]['messages'] = array_merge($prevResult[$ident]['messages'], $data['messages']);
            $merged[$ident]['totalTime'] = $prevResult[$ident]['totalTime'] + $data['totalTime'];
        }

        return $merged;
    }

    /**
     * Start of database profiling
     */
    public function dbStart($identifier = 'CORE')
    {
        if ($this->isActive()) {
            $this->dbProfiles[$identifier] = [];
            $this->dbLastIdentifier = $identifier;
        }
    }

    /**
     * Stop of database profiling
     */
    public function dbStop($identifier = 'CORE')
    {
        if ($this->isActive()) {
            unset($this->dbProfiles[$identifier]);
            $this->dbLastIdentifier = 'CORE';
        }
    }

    /**
     * Start of database query profiling
     *
     * @param string $query
     * @return type
     */
    public function dbQueryStart($query)
    {
        if ($this->isActive()) {
            $this->dbLastQueryIdentifier = microtime();

            $this->dbProfiles[$this->dbLastIdentifier][$this->dbLastQueryIdentifier]['startTime'] = microtime(true);
            $this->dbProfiles[$this->dbLastIdentifier][$this->dbLastQueryIdentifier]['query'] = $query;
        }
    }

    /**
     * End of database query profiling
     *
     * @param mixed $totalRows
     * @return type
     */
    public function dbQueryStop($totalRows)
    {
        if ($this->isActive()) {
            $startTime = $this->dbProfiles[$this->dbLastIdentifier][$this->dbLastQueryIdentifier]['startTime'];
            $this->dbProfiles[$this->dbLastIdentifier][$this->dbLastQueryIdentifier]['execTime'] = round(microtime(true) - $startTime, 8) * 1000;
            $this->dbProfiles[$this->dbLastIdentifier][$this->dbLastQueryIdentifier]['totalRows'] = $totalRows;
            $this->dbProfiles[$this->dbLastIdentifier][$this->dbLastQueryIdentifier]['backTrace'] = debug_backtrace();
        }
    }

    /**
     * Static wrapper for _display function
     * @return string
     */
    public static function display()
    {
        $profiler = self::getInstance();
        return $profiler->_display();
    }

    /**
     * Loads profiler result from file and return it
     * @return string
     */
    public function _display()
    {
        if ($this->isActive()) {
            if (file_exists($this->profilerFile)) {
                $content = file_get_contents($this->profilerFile);
                $this->profiles = json_decode($content, true);
                file_put_contents($this->profilerFile, '');

                return $this->process();
            } else {
                return '';
            }
        } else {
            return '';
        }
    }

    /**
     * Process profiler saved result and return html string for display
     */
    private function process()
    {
        if ($this->isActive()) {
            $str = '<link href="/public/css/plugins/profiler.min.css" media="screen" rel="stylesheet" type="text/css" />'
                    . '<div id="profiler">';

            foreach ($this->profiles as $ident => $profile) {
                $str .= "<i class='profiler-logo'></i><div class='profiler-row-wrapper'>"
                        . "<div class='profiler-basic'>"
                        . "<span class='border-right-black' title='Profile Identifier'>{$ident}</span>"
                        . "<span><i class='fi-comment large'></i> <a href='#' class='profiler-show' data-rel='messages' value='{$ident}'>Messages</a></span>"
                        . "<span><i class='fi-database large'></i> <a href='#' class='profiler-show' data-rel='query' value='{$ident}'>Query: " . count($profile['dbProfiles']) . "</a></span>"
                        . "<span><i class='fi-list large'></i> <a href='#' class='profiler-show' data-rel='variables' value='{$ident}'>Global variables</a></span>"
                        . "</div>"
                        . "<div class='statistics'>"
                        . "<span title='Request URI'><i class='fi-web large'></i> {$profile['requestUri']}</span>"
                        . "<span title='Backend execution time [s]'><i class='fi-clock large'></i> {$profile['totalTime']}</span>"
                        . "<span title='Memory peak usage'><i class='fi-graph-bar large'></i> {$profile['endMemoryPeakUsage']}</span>"
                        . "<span title='Memory usage'><i class='fi-graph-bar large'></i> {$profile['endMomoryUsage']}</span>"
                        . "</div>"
                        . "</div>";

                // ---------------------- messages -----------------------------
                $str .= "<div class='sub-data-table' id='{$ident}_messages'><pre>";
                $str .= print_r($profile['messages'], true);
                $str .= "</pre></div>";

                // -------------------- sql queries ----------------------------
                $str .= "<div class='sub-data-table' id='{$ident}_query'>"
                        . "<table><tr class='header'>"
                        . "<td colspan=5>Query</td><td>Exec time [ms]</td><td>Returned rows</td><td colspan=6>Backtrace</td></tr>";

                foreach ($profile['dbProfiles'] as $key => $value) {
                    $str .= "<tr>";
                    $str .= "<td colspan=5 class='query'>{$value['query']}</td>";
                    if ($value['execTime'] > 100) {
                        $str .= "<td class='width-7 red'>{$value['execTime']}</td>";
                    } else {
                        $str .= "<td class='width-7'>{$value['execTime']}</td>";
                    }
                    $str .= "<td class='width-7'>{$value['totalRows']}</td>";
                    $str .= "<td colspan=6 class=\"backtrace\"><div>";

                    foreach ($value['backTrace'] as $key => $trace) {
                        isset($trace['file']) ? $file = $trace['file'] : $file = '';
                        isset($trace['line']) ? $line = $trace['line'] : $line = '';
                        isset($trace['class']) ? $class = $trace['class'] : $class = '';
                        $str .= $key . " " . $file . ":" . $line . ":" . $class . ":" . $trace['function'] . "<br/>";
                    }
                    $str .= "</div></td></tr>";
                }

                $str .= "</table></div>";

                // ----------------- global variables --------------------------
                $str .= "<div class='sub-data-table' id='{$ident}_variables'>";

                foreach ($profile['data'] as $ident => $data) {
                    $str .= "<table class='width-33-left'><tr class='header'><td colspan=2>{$ident}</td></tr>";

                    foreach ($data as $key => $value) {

                        if (is_array($value)) {
                            $arrKey = array_keys($value);

                            if (count($arrKey)) {
                                $str .= "<tr><td>{$key}</td><td>{$arrKey[0]}</td></tr>";
                            }else{
                                $str .= "<tr><td>{$key}</td><td></td></tr>";
                            }
                        } else {
                            $str .= "<tr><td>{$key}</td><td>{$value}</td></tr>";
                        }
                    }
                    $str .= "</table>";
                }
                $str .= "</div>";
            }

            $str .= '</div><script type="text/javascript" src="/public/js/plugins/profiler.min.js"></script>';

            return $str;
        }
    }

    public function addMessage($ident, $data)
    {
        $this->messages[$ident][] = $data;
        return $this;
    }

    public function getProfiles()
    {
        return $this->profiles;
    }

    public function getDbProfiles()
    {
        return $this->dbProfiles;
    }

    public function getActive()
    {
        return $this->active;
    }

    public function getDbLastIdentifier()
    {
        return $this->dbLastIdentifier;
    }

    public function getDbLastQueryIdentifier()
    {
        return $this->dbLastQueryIdentifier;
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function setProfiles($profiles)
    {
        $this->profiles = $profiles;
        return $this;
    }

    public function setDbProfiles($dbProfiles)
    {
        $this->dbProfiles = $dbProfiles;
        return $this;
    }

    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    public function setDbLastIdentifier($dbLastIdentifier)
    {
        $this->dbLastIdentifier = $dbLastIdentifier;
        return $this;
    }

    public function setDbLastQueryIdentifier($dbLastQueryIdentifier)
    {
        $this->dbLastQueryIdentifier = $dbLastQueryIdentifier;
        return $this;
    }

    public function setMessages($messages)
    {
        $this->messages = $messages;
        return $this;
    }

}
