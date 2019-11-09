<?php

namespace THCFrame\Database;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Database\Exception;
use THCFrame\Database\Connector as Connector;
use THCFrame\Filesystem\FileManager;

/**
 * Mysqldump
 */
class Mysqldump extends Base
{

    const MAX_LINE_SIZE = 500;

    /**
     *
     * @var THCFrame\Database\ConnectionHandler
     */
    private $connectionHandler;

    /**
     *
     * @var THCFrame\Filesystem\FileManager
     */
    private $filemanager;
    private $backupFileName = null;
    private $backupDir = null;
    private $fileHandler = null;
    private $settings = [];
    private $backupFiles = [];
    private $defaultSettings = [
        'include-tables' => [],
        'exclude-tables' => [],
        'exclude-tables-reqex' => [],
        'no-data' => false,
        'only-data' => false,
        'add-drop-table' => true,
        'single-transaction' => true,
        'lock-tables' => false,
        'add-locks' => true,
        'disable-foreign-keys-check' => true,
        'extended-insert' => true,
        'write-comments' => true,
        'use-file-compression' => true
    ];

    /**
     * Object constructor
     *
     * @param type $settings
     */
    public function __construct($settings = [])
    {
        parent::__construct();

        $this->connectionHandler = Registry::get('database');

        $this->filemanager = new FileManager();
        $defaultDir = APP_PATH . '/temp/db/';

        if (!is_dir($defaultDir)) {
            $this->filemanager->mkdir($defaultDir);
        }

        $this->backupDir = $defaultDir;

        $this->prepareSettings($settings);
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->connectionHandler->disconnectAll();
    }

    /**
     *
     * @param type $settings
     */
    private function prepareSettings(array $settings)
    {
        $dbIdents = $this->connectionHandler->getIdentifications();

        if (!empty($dbIdents)) {
            foreach ($dbIdents as $id) {
                if (!empty($settings[$id])) {
                    $this->settings[$id] = array_replace_recursive($this->defaultSettings, $settings[$id]);
                } else {
                    $this->settings[$id] = $this->defaultSettings;
                }
            }
        }
    }

    /**
     *
     * @param Connector $dbc
     * @param type $dbid
     * @return array
     */
    private function getTables(Connector $dbc, $dbid)
    {
        $sqlResult = $dbc->execute('SHOW TABLES');
        $tables = [];

        while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
            if (empty($this->settings[$dbid]['include-tables']) ||
                    (!empty($this->settings[$dbid]['include-tables']) &&
                    in_array($row['Tables_in_' . $dbc->getSchema()], $this->settings[$dbid]['include-tables'], true))) {
                array_push($tables, $row['Tables_in_' . $dbc->getSchema()]);
            }
        }

        return $tables;
    }

    /**
     *
     * @param Connector $dbc
     * @param type $dbid
     * @param type $table
     * @return boolean
     */
    private function getTableStructure(Connector $dbc, $dbid, $table)
    {
        $sqlResult = $dbc->execute("SHOW CREATE TABLE `{$table}`");

        while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
            if ($this->settings[$dbid]['only-data'] === true) {
                return true;
            }

            if (isset($row['Create Table'])) {
                if ($this->settings[$dbid]['write-comments'] === true) {
                    $this->write(
                            '-- -----------------------------------------------------' . PHP_EOL .
                            "-- Table structure for table `{$table}` --" . PHP_EOL);
                }

                if ($this->settings[$dbid]['add-drop-table']) {
                    $this->write("DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL);
                }

                $this->write($row['Create Table'] . ';' . PHP_EOL);
                return true;
            }
        }

        return false;
    }

    /**
     * @param \THCFrame\Database\Connector $dbc
     * @param $dbid
     * @param $tablename
     * @throws \Exception
     */
    private function getTableValues(Connector $dbc, $dbid, $tablename)
    {
        if ($this->settings[$dbid]['write-comments'] === true) {
            $this->write('--' . PHP_EOL .
                    "-- Dumping data for table `{$tablename}` --" . PHP_EOL);
        }

        $dbSetting = $this->settings[$dbid];

        if ($dbSetting['single-transaction']) {
            //$dbc->query('SET GLOBAL TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $dbc->beginTransaction();
        }

        if ($dbSetting['lock-tables']) {
            $dbc->execute("LOCK TABLES `{$tablename}` READ LOCAL");
        }

        if ($dbSetting['add-locks']) {
            $this->write("LOCK TABLES `{$tablename}` WRITE;" . PHP_EOL);
        }

        $onlyOnce = true;
        $lineSize = 0;
        $sqlResult = $dbc->execute("SELECT * FROM `{$tablename}`");

        while ($row = $sqlResult->fetch_array(MYSQLI_ASSOC)) {
            $vals = [];
            foreach ($row as $val) {
                $vals[] = is_null($val) ? 'NULL' : addslashes($val);
            }

            if ($onlyOnce || !$dbSetting['extended-insert']) {
                $lineSize += $this->write(html_entity_decode(
                                "INSERT INTO `{$tablename}` VALUES ('" . implode("', '", $vals) . "')", ENT_QUOTES, 'UTF-8'));
                $onlyOnce = false;
            } else {
                $lineSize += $this->write(html_entity_decode(",('" . implode("', '", $vals) . "')", ENT_QUOTES, 'UTF-8'));
            }

            if (($lineSize > self::MAX_LINE_SIZE) || !$dbSetting['extended-insert']) {
                $onlyOnce = true;
                $lineSize = $this->write(';' . PHP_EOL);
            }
        }

        if (!$onlyOnce) {
            $this->write(';' . PHP_EOL);
        }
        if ($dbSetting['add-locks']) {
            $this->write('UNLOCK TABLES;' . PHP_EOL);
        }
        if ($dbSetting['single-transaction']) {
            $dbc->commitTransaction();
        }
        if ($dbSetting['lock-tables']) {
            $dbc->execute('UNLOCK TABLES');
        }

        unset($dbSetting);

        return;
    }

    /**
     * Returns header for dump file
     *
     * @param Connector $dbc
     * @param type $dbid
     * @return string
     */
    private function createHeader(Connector $dbc, $dbid)
    {
        $header = '';

        if ($this->settings[$dbid]['write-comments'] === true) {
            $header .= '-- mysqldump-php SQL Dump' . PHP_EOL .
                    '--' . PHP_EOL .
                    "-- Host: {$dbc->getHost()}" . PHP_EOL .
                    '-- Generation Time: ' . date('r') . PHP_EOL .
                    '--' . PHP_EOL .
                    "-- Database: `{$dbc->getSchema()}`" . PHP_EOL .
                    '--' . PHP_EOL;
        }

        if ($this->settings[$dbid]['disable-foreign-keys-check']) {
            $header .= 'SET FOREIGN_KEY_CHECKS=0;' . PHP_EOL;
        }

        return $header;
    }

    /**
     * Returns footer for dump file
     *
     * @param type $dbid
     * @return string
     */
    private function createFooter($dbid)
    {
        $footer = '';
        if ($this->settings[$dbid]['disable-foreign-keys-check']) {
            $footer .= 'SET FOREIGN_KEY_CHECKS=1;' . PHP_EOL;
        }

        return $footer;
    }

    /**
     * Open file
     *
     * @param string $filename
     * @return boolean
     */
    private function open($filename)
    {
        $this->fileHandler = fopen($filename, 'wb');

        if ($this->fileHandler === false) {
            return false;
        }
        return true;
    }

    /**
     * @param $str
     * @return false|int
     * @throws Exception\Mysqldump
     */
    private function write($str)
    {
        $bytesWritten = 0;
        if (($bytesWritten = fwrite($this->fileHandler, $str)) === false) {
            throw new Exception\Mysqldump('Writting to file failed!', 4);
        }
        return $bytesWritten;
    }

    /**
     * @return bool
     */
    private function close()
    {
        return fclose($this->fileHandler);
    }

    /**
     * Main private method
     * Creates file and write database dump into it
     *
     * @param Connector $db     connector instance
     * @param string    $id     database identification
     * @throws Exception\Mysqldump
     */
    private function writeData(Connector $db, $id)
    {

        if ($this->backupFileName === null) {
            $filename = $this->backupDir . $db->getSchema() . '_' . date('Y-m-d') . '.sql';
        } else {
            $filename = $this->backupDir . $this->backupFileName;
        }

        if (!$this->open($filename)) {
            throw new Exception\Mysqldump(sprintf('Output file %s is not writable', $filename), 2);
        }

        Event::fire('framework.mysqldump.create.before', [$filename]);

        $this->write($this->createHeader($db, $id));
        $tables = $this->getTables($db, $id);

        if (!empty($tables)) {
            foreach ($tables as $table) {
                if (in_array($table, $this->settings[$id]['exclude-tables'], true)) {
                    continue;
                }

                foreach ($this->settings[$id]['exclude-tables-reqex'] as $regex) {
                    if (mb_ereg_match($regex, $table)) {
                        continue 2;
                    }
                }

                $is_table = $this->getTableStructure($db, $id, $table);
                if ($is_table === true && $this->settings[$id]['no-data'] === false) {
                    $this->getTableValues($db, $id, $table);
                }
            }
        }

        $this->write($this->createFooter($id));
        Event::fire('framework.mysqldump.create.after', [$filename]);

        $this->close();
        $this->backupFiles[$id] = $filename;
    }

    /**
     *
     * @param array $files
     */
    private function compressBackupFiles(array $files = [])
    {
        if (!empty($files)) {
            foreach ($files as $dbid => $path) {
                if ($this->settings[$dbid]['use-file-compression'] === true) {
                    if (file_exists($path)) {
                        $this->filemanager->gzCompressFile($path);
                        @unlink($path);
                    }
                }
            }
        }
    }

    /**
     * Main public method
     * Create mysql database dump of all connected databases or one specific
     * database based on parameter
     *
     * @param string        $dbId       database identification
     * @return boolean
     * @throws Exception\Mysqldump
     */
    public function create($dbId = null)
    {
        $dbIdents = $this->connectionHandler->getIdentifications();

        if (empty($dbIdents)) {
            throw new Exception\Mysqldump('No connected database found');
        }

        if (null !== $dbId) {
            if (in_array($dbId, $dbIdents)) {
                $db = $this->connectionHandler->get($dbId);
                $this->writeData($db, $dbId);

                if (!empty($this->backupFiles)) {
                    $this->compressBackupFiles($this->backupFiles);
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new Exception\Mysqldump(sprintf('Database with identification %s is not connected', $dbId));
            }
        } else {
            foreach ($dbIdents as $id) {
                $db = $this->connectionHandler->get($id);
                $this->writeData($db, $id);
                unset($db);
            }

            if (!empty($this->backupFiles)) {
                $this->compressBackupFiles($this->backupFiles);
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @param null $id
     * @return array|mixed|null
     */
    public function getDumpFile($id = null)
    {
        if ($id === null) {
            return $this->backupFiles;
        } else {
            if (array_key_exists($id, $this->backupFiles)) {
                return $this->backupFiles[$id];
            } else {
                return null;
            }
        }
    }

    /**
     *
     * @param type $name
     * @return \THCFrame\Database\Mysqldump
     */
    public function setBackupName($name)
    {
        $this->backupFileName = $name;
        return $this;
    }

    /**
     *
     * @param type $dir
     * @return \THCFrame\Database\Mysqldump
     */
    public function setBackupDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->backupDir = $dir;
        return $this;
    }

    /**
     * Download database dump
     *
     */
    public function downloadDump()
    {
        if (!empty($this->backupFiles)) {
            foreach ($this->backupFiles as $filename) {
                $mime = 'text/x-sql';
                header('Content-Type: application/octet-stream');
                header("Content-Transfer-Encoding: Binary");
                header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\"");
                header('Content-Length: ' . filesize($filename));
                ob_clean();
                readfile($filename);
            }
        }
        exit;
    }

}
