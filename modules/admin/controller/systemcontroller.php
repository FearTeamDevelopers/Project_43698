<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use THCFrame\Request\RequestMethods;
use THCFrame\Database\Mysqldump;
use THCFrame\Events\Events as Event;
use THCFrame\Configuration\Model\ConfigModel;
use THCFrame\Profiler\Profiler;
use THCFrame\Router\Model\RedirectModel;
use THCFrame\Filesystem\LineCounter;

/**
 * 
 */
class SystemController extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        
    }

    /**
     * Ability to clear cache from administration
     * 
     * @before _secured, _admin
     */
    public function clearCache()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('clearCache')) {
            Event::fire('admin.log', array('success'));
            $this->getCache()->clearCache();

            $view->successMessage('Cache has been successfully deleted');
            self::redirect('/admin/system/');
        }
    }

    /**
     * Create db bakcup
     * 
     * @before _secured, _admin
     */
    public function createDatabaseBackup()
    {
        $view = $this->getActionView();
        $dump = new Mysqldump();

        try {
            if ($dump->create()) {
                $view->successMessage('Database backup has been successfully created');
                Event::fire('admin.log', array('success', 'Database backup'));
            } else {
                $view->errorMessage('Database backup could not be created');
                Event::fire('admin.log', array('fail', 'Database backup'));
            }
        } catch (\THCFrame\Database\Exception\Mysqldump $ex) {
            $view->errorMessage($ex->getMessage());
            Event::fire('admin.log', array('fail', 'Database backup',
                'Error: ' . $ex->getMessage()));
        }

        self::redirect('/admin/system/');
    }

    /**
     * Get admin log
     * 
     * @before _secured, _superadmin
     */
    public function showAdminLog()
    {
        $view = $this->getActionView();
        $log = \Admin\Model\AdminLogModel::all(array(), array('*'), array('created' => 'DESC'), 250);
        $view->set('adminlog', $log);
    }

    /**
     * Edit application settings
     * 
     * @before _secured, _admin
     */
    public function settings()
    {
        $view = $this->getActionView();
        $config = ConfigModel::all();
        $view->set('config', $config);

        if (RequestMethods::post('submitEditSet')) {
            if ($this->checkCSRFToken() !== true) {
                self::redirect('/admin/');
            }
            $errors = array();

            foreach ($config as $conf) {
                $oldVal = $conf->getValue();
                $conf->value = RequestMethods::post($conf->getXkey());

                if ($conf->validate()) {
                    Event::fire('admin.log', array('success', $conf->getXkey() . ': ' . $oldVal . ' - ' . $conf->getValue()));
                    $conf->save();
                } else {
                    Event::fire('admin.log', array('fail', $conf->getXkey() . ': ' . json_encode($conf->getErrors())));
                    $error = $conf->getErrors();
                    $errors[$conf->xkey] = array_shift($error);
                }
            }

            if (empty($errors)) {
                $view->successMessage(self::SUCCESS_MESSAGE_2);
                self::redirect('/admin/system/');
            } else {
                $view->set('errors', $errors);
            }
        }
    }

    /**
     * Get profiler result
     * 
     * @before _secured
     */
    public function showProfiler()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        echo Profiler::display();
    }

    /**
     * Generate sitemap.xml
     * 
     * @before _secured, _admin
     */
    public function generateSitemap()
    {
        $view = $this->getActionView();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
        <urlset
            xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . PHP_EOL;

        $xmlEnd = '</urlset>';

        $host = RequestMethods::server('HTTP_HOST');

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

        Event::fire('admin.log', array('success', 'Links count: ' . $linkCounter));
        $view->successMessage('Soubor sitemap.xml byl aktualizován');
        self::redirect('/admin/system/');
    }

    /**
     * @before _secured, _superadmin
     */
    public function linecounter()
    {
        if (ENV !== 'dev') {
            exit;
        }

        $view = $this->getActionView();

        $counter = new LineCounter();
        $totalLines = $counter->countLines(APP_PATH);
        $fileCounter = $counter->getFileCounter();

        $view->set('totallines', $totalLines)
                ->set('filecounter', $fileCounter);
    }

    /**
     * Create db backup by cron
     * 
     * @before _cron
     */
    public function cronDatabaseBackup()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        $days = 10;
        $path = APP_PATH.'/temp/db/';

        if ($handle = opendir($path)) {
            while (false !== ($file = readdir($handle))) {
                if (is_file($path . $file)) {
                    if (filemtime($path . $file) < ( time() - ( $days * 24 * 60 * 60 ) )) {
                        unlink($path . $file);
                    }
                }
            }
        }

        $dump = new Mysqldump();

        try {
            if ($dump->create()) {
                Event::fire('admin.log', array('success', 'Database backup'));
            } else {
                Event::fire('admin.log', array('fail', 'Database backup'));
            }
        } catch (\THCFrame\Database\Exception\Mysqldump $ex) {
            Event::fire('admin.log', array('fail', 'Database backup',
                'Error: ' . $ex->getMessage()));
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

        Event::fire('admin.log', array('success', 'Links count: ' . $linkCounter));
    }

}
