<?php

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Database\Mysqldump;
use THCFrame\Events\Events as Event;
use THCFrame\Configuration\Model\Config;
use THCFrame\Filesystem\FileManager;
use THCFrame\Profiler\Profiler;

/**
 * 
 */
class Admin_Controller_System extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        
    }

    /**
     * @before _secured, _admin
     */
    public function clearCache()
    {
        $view = $this->getActionView();

        if (RequestMethods::post('clearCache')) {
            Event::fire('admin.log', array('success'));
            $cache = Registry::get('cache');
            $cache->clearCache();
            $view->successMessage('Cache has been successfully deleted');
            self::redirect('/admin/system/');
        }
    }

    /**
     * Create and download db bakcup
     * 
     * @before _secured, _admin
     */
    public function createDatabaseBackup()
    {
        $view = $this->getActionView();
        $dump = new Mysqldump();
        $fm = new FileManager();

        if (!is_dir(APP_PATH . '/temp/db/')) {
            $fm->mkdir(APP_PATH . '/temp/db/');
        }

        $dump->create();
        $view->successMessage('Database backup has been successfully created');
        Event::fire('admin.log', array('success', 'Database backup ' . $dump->getBackupName()));
        unset($fm);
        unset($dump);
        self::redirect('/admin/system/');
    }

    /**
     * @before _secured, _superadmin
     */
    public function showAdminLog()
    {
        $view = $this->getActionView();
        $log = Admin_Model_AdminLog::all(array(), array('*'), array('created' => 'DESC'));
        $view->set('adminlog', $log);
    }

    /**
     * @before _secured, _admin
     */
    public function settings()
    {
        $view = $this->getActionView();
        $config = Config::all();
        $view->set('config', $config);

        if (RequestMethods::post('submitEditSet')) {
            if ($this->checkCSRFToken() !== true){
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
                    Event::fire('admin.log', array('fail', $conf->getXkey() . ': ' . $conf->getValue()));
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
     * @before _secured
     */
    public function showProfiler()
    {
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

        echo Profiler::display();
    }

    /**
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
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';

        $xmlEnd = '</urlset>';

        $host = RequestMethods::server('HTTP_HOST');

        $pageContentXml = "<url><loc>http://{$host}</loc></url>"
                . "<url><loc>http://{$host}/o-nas</loc></url>"
                . "<url><loc>http://{$host}/cenik</loc></url>"
                . "<url><loc>http://{$host}/kontakty</loc></url>"
                . "<url><loc>http://{$host}/reference</loc></url>" . PHP_EOL;

        

        file_put_contents('./sitemap.xml', $xml . $pageContentXml . $xmlEnd);

        Event::fire('admin.log', array('success'));
        $view->successMessage('Soubor sitemap.xml byl aktualizován');
        self::redirect('/admin/system/');
    }

    /**
     * 
     */
    public function fillDatabase()
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        
        $NEWS_ROW_COUNT = 750;
        $ACTIONS_ROW_COUNT = 600;
        $REPORTS_ROW_COUNT = 900;
        $content = App_Model_PageContent::first(array('urlKey = ?' => 'kurzy-sdi'), array('body'));
        $LARGE_TEXT = $content->getBody();
        $META_DESC = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse efficitur viverra libero, at dapibus sapien placerat a. '
                . 'In efficitur tortor in nulla auctor tristique. Pellentesque non nisi mollis, tincidunt purus rutrum, ornare sem.';
        
        for($i = 0; $i<$NEWS_ROW_COUNT; $i++){
            $news = new App_Model_News(array(
                'title' => 'News-'.$i.'-'.time(),
                'userId' => 1,
                'userAlias' => 'System',
                'urlKey' => 'News-'.$i.'-'.time(),
                'approved' => 1,
                'archive' => 0,
                'shortBody' => $LARGE_TEXT,
                'body' => $LARGE_TEXT,
                'expirationDate' => '2016-01-01',
                'rank' => 1,
                'keywords' => 'bla,bla,bla,bla',
                'metaTitle' => 'News-'.$i.'-'.time(),
                'metaDescription' => $META_DESC
            ));
            
            $news->save();
        }
        
        for($i = 0; $i<$NEWS_ROW_COUNT; $i++){
            $action = new App_Model_Action(array(
                'title' => 'Actions-'.$i.'-'.time(),
                'userId' => 1,
                'userAlias' => 'System',
                'urlKey' => 'Actions-'.$i.'-'.time(),
                'approved' => 1,
                'archive' => 0,
                'shortBody' => $LARGE_TEXT,
                'body' => $LARGE_TEXT,
                'expirationDate' => '2016-01-01',
                'rank' => 1,
                'keywords' => 'action,bla,bla'
            ));
            
            $action->save();
        }
    }

}
