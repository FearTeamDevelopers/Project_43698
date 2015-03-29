<?php

namespace Cron\Controller;

use Cron\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Database\Mysqldump;
use THCFrame\Events\Events as Event;
use THCFrame\Router\Model\RedirectModel;
use THCFrame\Filesystem\FileManager;

/**
 * 
 */
class IndexController extends Controller
{

    /**
     * Comprime and then remove old files from folder
     * 
     * @param type $path
     * @param type $days
     */
    private function _removeOldFiles($path, $days = 7)
    {
        $fm = new FileManager();
        
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            return;
        }

        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if (is_file($path . $file)) {
                    if (filemtime($path . $file) < ( time() - ( $days * 24 * 60 * 60 ) )) {
                        $fm->gzCompressFile($path . $file);
                        unlink($path . $file);
                    }
                }
            }
        }
    }

    /**
     * Reconnect to the test database
     */
    private function _resertTestConnection()
    {
        $oldDb = new \THCFrame\Database\Database();
        $db = $oldDb->initializeDirectly(array(
            'type' => 'mysql',
            'host' => 'mysql4.ebola.cz',
            'username' => 'hastrmancz_ts',
            'password' => 'SqlTstHrm-5102-',
            'schema' => 'hastrman_004'
        ));

//        $db = $oldDb->initializeDirectly(array(
//            'type' => 'mysql',
//            'host' => 'localhost',
//            'username' => 'root',
//            'password' => '',
//            'schema' => 'hastrman_test'
//        ));

        return $db;
    }
    
    /**
     * Reconnect to the database
     */
    private function _resertConnections()
    {
        $config = Registry::get('configuration');
        Registry::get('database')->disconnectAll();

        $database = new \THCFrame\Database\Database();
        $connectors = $database->initialize($config);
        Registry::set('database', $connectors);
        
        unset($config);
        unset($database);
        unset($connectors);
    }

    /**
     * Create daily db backup by cron
     * 
     * @before _cron
     */
    public function cronDailyDatabaseBackup()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $path = APP_PATH . '/temp/db/day/';
        $this->_removeOldFiles($path);

        $dump = new Mysqldump();
        $dump->setBackupDir($path);

        try {
            if ($dump->create()) {
                Event::fire('cron.log', array('success', 'Database backup'));
            } else {
                Event::fire('cron.log', array('fail', 'Database backup'));
                $this->sendEmail('Error in mysqldump class while creating database backup', 'ERROR: Cron databaseBackup');
            }
        } catch (\THCFrame\Database\Exception\Mysqldump $ex) {
            Event::fire('cron.log', array('fail', 'Database backup',
                'Error: ' . $ex->getMessage()));
            $this->sendEmail('Error while creating database backup: ' . $ex->getMessage(), 'ERROR: Cron databaseBackup');
        }
    }
    
    /**
     * Create monthly db backup by cron
     * 
     * @before _cron
     */
    public function cronMonthlyDatabaseBackup()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $path = APP_PATH . '/temp/db/month/';

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $dump = new Mysqldump();
        $dump->setBackupDir($path);

        try {
            if ($dump->create()) {
                Event::fire('cron.log', array('success', 'Database backup'));
            } else {
                Event::fire('cron.log', array('fail', 'Database backup'));
                $this->sendEmail('Error in mysqldump class while creating database backup', 'ERROR: Cron databaseBackup');
            }
        } catch (\THCFrame\Database\Exception\Mysqldump $ex) {
            Event::fire('cron.log', array('fail', 'Database backup',
                'Error: ' . $ex->getMessage()));
            $this->sendEmail('Error while creating database backup: ' . $ex->getMessage(), 'ERROR: Cron databaseBackup');
        }
    }

    /**
     * Clone production database to test
     * 
     * @before _cron
     */
    public function cronDatabaseProdToTest()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        $starttime = microtime(true);

        $dbDataPath = APP_PATH . '/temp/db/data/';
        $dbStructurePath = APP_PATH . '/temp/db/structure/';

        $this->_removeOldFiles($dbDataPath, 31);
        $this->_removeOldFiles($dbStructurePath, 31);

        $settingsNoData = array('main' => array(
                'no-data' => true,
                'write-comments' => false,
                'disable-foreign-keys-check' => false,
                'use-file-compression' => false
        ));

        $settingsOnlyData = array('main' => array(
                'only-data' => true,
                'add-locks' => false,
                'disable-foreign-keys-check' => false,
                'extended-insert' => false,
                'write-comments' => false,
                'use-file-compression' => false
        ));
        $dumpOnlyData = new Mysqldump($settingsOnlyData);
        $dumpNoData = new Mysqldump($settingsNoData);

        $dumpOnlyData->setBackupDir($dbDataPath);
        $dumpNoData->setBackupDir($dbStructurePath);

        try {
            if ($dumpNoData->create('main') && $dumpOnlyData->create('main')) {
                $db = $this->_resertTestConnection();

                $dbStructureSql = file_get_contents($dumpNoData->getDumpFile('main'));
                $sqls = explode(';', $dbStructureSql);

                $db->execute('SET FOREIGN_KEY_CHECKS=0');

                foreach ($sqls as $sql) {
                    $db->execute($sql);
                }

                $db = $this->_resertTestConnection();

                $dataSql = file_get_contents($dumpOnlyData->getDumpFile('main'));
                $dataSqlArr = explode('INSERT INTO', $dataSql);

                $db->execute('SET FOREIGN_KEY_CHECKS=0');

                if (!empty($dataSqlArr)) {
                    $i = 0;
                    foreach ($dataSqlArr as $query) {
                        if (empty($query))
                            continue;

                        $sql = 'INSERT INTO ' . trim($query);
                        $db->execute($sql);
                        $i++;

                        if ($i == 500) {
                            $db = $this->_resertTestConnection();
                            $db->execute('SET FOREIGN_KEY_CHECKS=0');
                            $i = 0;
                        }
                    }
                }

                $db->execute('SET FOREIGN_KEY_CHECKS=1');

                $time = round(microtime(true) - $starttime, 2);
                Event::fire('cron.log', array('success', sprintf('Database clone to test took %s sec', $time)));
            } else {
                Event::fire('cron.log', array('fail', 'Database clone to test'));
                $this->sendEmail('Unknown error', 'ERROR: Cron clone production database');
            }
        } catch (\THCFrame\Database\Exception\Mysqldump $ex) {
            Event::fire('cron.log', array('fail', 'Database clone to test',
                'Error: ' . $ex->getMessage()));
            $this->sendEmail('Error: ' . $ex->getMessage(), 'ERROR: Cron clone production database');
        }
    }

    /**
     * Generate sitemap.xml by cron
     * 
     * @before _cron
     */
    public function cronGenerateSitemap()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <urlset
            xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;

        $xmlEnd = '</urlset>';

        $host = RequestMethods::server('HTTP_HOST');

        try {
            $pageContent = \App\Model\PageContentModel::all(array('active = ?' => true));
            $redirects = RedirectModel::all(array('module = ?' => 'app'));
            $news = \App\Model\NewsModel::all(array('active = ?' => true, 'approved = ?' => 1), array('urlKey'));
            $reports = \App\Model\ReportModel::all(array('active = ?' => true, 'approved = ?' => 1), array('urlKey'));
            $actions = \App\Model\ActionModel::all(array('active = ?' => true, 'approved = ?' => 1), array('urlKey'));

            $redirectArr = array();
            if (null !== $redirects) {
                foreach ($redirects as $redirect) {
                    $redirectArr[$redirect->getToPath()] = $redirect->getFromPath();
                }
            }

            $articlesXml = '';
            $pageContentXml = "<url><loc>http://{$host}</loc></url>" . PHP_EOL
                    . "<url><loc>http://{$host}/akce</loc></url>"
                    . "<url><loc>http://{$host}/probehleakce</loc></url>"
                    . "<url><loc>http://{$host}/archivakci</loc></url>"
                    . "<url><loc>http://{$host}/archivnovinek</loc></url>"
                    . "<url><loc>http://{$host}/archivreportazi</loc></url>"
                    . "<url><loc>http://{$host}/reportaze</loc></url>"
                    . "<url><loc>http://{$host}/novinky</loc></url>"
                    . "<url><loc>http://{$host}/galerie</loc></url>"
                    . "<url><loc>http://{$host}/bazar</loc></url>" . PHP_EOL;

            $linkCounter = 10;

            if (null !== $pageContent) {
                foreach ($pageContent as $content) {
                    $pageUrl = '/page/' . $content->getUrlKey();
                    if (array_key_exists($pageUrl, $redirectArr)) {
                        $pageUrl = $redirectArr[$pageUrl];
                    }
                    $pageContentXml .= "<url><loc>http://{$host}{$pageUrl}</loc></url>" . PHP_EOL;
                    $linkCounter++;
                }
            }

            if (null !== $news) {
                foreach ($news as $_news) {
                    $articlesXml .= "<url><loc>http://{$host}/novinky/r/{$_news->getUrlKey()}</loc></url>" . PHP_EOL;
                    $linkCounter++;
                }
            }

            if (null !== $actions) {
                foreach ($actions as $action) {
                    $articlesXml .= "<url><loc>http://{$host}/akce/r/{$action->getUrlKey()}</loc></url>" . PHP_EOL;
                    $linkCounter++;
                }
            }

            if (null !== $reports) {
                foreach ($reports as $report) {
                    $articlesXml .= "<url><loc>http://{$host}/reportaze/r/{$report->getUrlKey()}</loc></url>" . PHP_EOL;
                    $linkCounter++;
                }
            }

            file_put_contents('./sitemap.xml', $xml . $pageContentXml . $articlesXml . $xmlEnd);
            $this->_resertConnections();

            Event::fire('cron.log', array('success', 'Links count: ' . $linkCounter));
        } catch (\Exception $ex) {
            Event::fire('cron.log', array('fail', 'Error while creating sitemap file: ' . $ex->getMessage()));
            $this->sendEmail('Error while creating sitemap file: ' . $ex->getMessage(), 'ERROR: Cron generateSitemap');
        }
    }

}
