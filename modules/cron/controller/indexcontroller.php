<?php

namespace Cron\Controller;

use Admin\Model\SitemapModel;
use Cron\Etc\Controller;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;

/**
 *
 */
class IndexController extends Controller
{

    /**
     * @param $dir
     * @return false|int
     */
    private function folderSize($dir)
    {
        $count_size = 0;
        $dir_array = scandir($dir);

        foreach ($dir_array as $key => $filename) {
            if ($filename != '..' && $filename != '.') {
                if (is_dir($dir . '/' . $filename)) {
                    $new_foldersize = $this->folderSize($dir . '/' . $filename);
                    $count_size += $new_foldersize;
                } elseif (is_file($dir . '/' . $filename)) {
                    $count_size += filesize($dir . '/' . $filename);
                }
            }
        }

        return $count_size;
    }

    /**
     * Reconnect to the database.
     */
    private function resertConnections()
    {
        $config = Registry::get('configuration');
        Registry::get('database')->disconnectAll();

        $database = new \THCFrame\Database\Database();
        $connectors = $database->initialize($config);
        Registry::set('database', $connectors);

        unset($config, $database, $connectors);
    }

    /**
     * Generate sitemap.xml by cron.
     *
     * @before _cron
     */
    public function generateSitemap()
    {
        try {
            $linkCounter = SitemapModel::generateSitemap();

            $this->resertConnections();
            Event::fire('cron.log', ['success', 'Links count: ' . $linkCounter]);
        } catch (\Exception $ex) {
            $this->resertConnections();
            Event::fire('cron.log', ['fail', 'Error while creating sitemap file: ' . $ex->getMessage()]);

            $message = $ex->getMessage() . PHP_EOL . $ex->getTraceAsString();

            $mailer = new \THCFrame\Mailer\Mailer([
                'body' => 'Error while creating sitemap file: ' . $message,
                'subject' => 'ERROR: Cron generateSitemap'
            ]);

            $mailer->setFrom('cron@hastrman.cz')
                    ->send();
        }
    }

    /**
     * Cron check database size and application disk space usage.
     *
     * @before _cron
     */
    public function systemCheck()
    {
        $connHandler = Registry::get('database');
        $dbIdents = $connHandler->getIdentifications();

        $message = '';

        foreach ($dbIdents as $id) {
            $db = $connHandler->get($id);
            $size = $db->getDatabaseSize();
            if ($size > 45) {
                $message .= sprintf('Database %s is growing large. Current size is %s MB', $db->getSchema(), $size).PHP_EOL;
            }
        }

        $applicationFolderSize = $this->folderSize(APP_PATH);
        $applicationFolderSizeMb = $applicationFolderSize / (1024 * 1024);
        if ($applicationFolderSizeMb > 600) {
            $message .= sprintf('Application folder is growing large. Current size is %s MB', $applicationFolderSizeMb).PHP_EOL;
        }

        if ($message !== '') {
            $mailer = new \THCFrame\Mailer\Mailer([
                'body' => $message,
                'subject' => 'WARNING: System chcek'
            ]);

            $mailer->setFrom('cron@hastrman.cz')
                    ->send();
        }
    }

    /**
     * Run File hash scan
     *
     * @before _cron
     */
    public function filehashscan()
    {
        $scanner = new \THCFrame\Security\FileHashScanner\Scanner();

        $scanner->scan();
        Event::fire('cron.log', ['success', 'File hash checked']);
    }

}
