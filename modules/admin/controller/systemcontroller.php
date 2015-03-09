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
                'Error: '.$ex->getMessage()));
        }

        self::redirect('/admin/system/');
    }
    
    /**
     * Copy live db into backup db
     * 
     * @before _cron
     */
    public function createCompleteDatabaseBackup()
    {
        
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
        $this->_willRenderActionView = false;
        $this->_willRenderLayoutView = false;

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
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'. PHP_EOL;

        $xmlEnd = '</urlset>';

        $host = RequestMethods::server('HTTP_HOST');
        
        $pageContent = \App\Model\PageContentModel::all(array('active = ?' => true));
        $redirects = RedirectModel::all(array('module = ?' => 'app'));
        $news = \App\Model\NewsModel::all(array('active = ?' => true, 'approved = ?' => 1), array('urlKey'));
        $reports = \App\Model\ReportModel::all(array('active = ?' => true, 'approved = ?' => 1), array('urlKey'));
        $actions = \App\Model\ActionModel::all(array('active = ?' => true, 'approved = ?' => 1), array('urlKey'));
        
        $redirectArr = array();
        if(null !== $redirects){
            foreach ($redirects as $redirect){
                $redirectArr[$redirect->getToPath()] = $redirect->getFromPath();
            }
        }
        
        $articlesXml = '';
        $pageContentXml = "<url><loc>http://{$host}</loc></url>". PHP_EOL
                . "<url><loc>http://{$host}/akce</loc></url>"
                . "<url><loc>http://{$host}/archivakci</loc></url>"
                . "<url><loc>http://{$host}/reportaze</loc></url>"
                . "<url><loc>http://{$host}/novinky</loc></url>"
                . "<url><loc>http://{$host}/galerie</loc></url>"
                . "<url><loc>http://{$host}/bazar</loc></url>". PHP_EOL;

        if (null !== $pageContent) {
            foreach ($pageContent as $content) {
                $pageUrl = '/page/'.$content->getUrlKey();
                if(array_key_exists($pageUrl, $redirectArr)){
                    $pageUrl = $redirectArr[$pageUrl];
                }
                $pageContentXml .= "<url><loc>http://{$host}{$pageUrl}</loc></url>". PHP_EOL;
            }
        }
        
        if(null !== $news){
            foreach ($news as $_news){
                $articlesXml .= "<url><loc>http://{$host}/novinky/r/{$_news->getUrlKey()}</loc></url>". PHP_EOL;
            }
        }
        
        if(null !== $actions){
            foreach ($actions as $action){
                $articlesXml .= "<url><loc>http://{$host}/akce/r/{$action->getUrlKey()}</loc></url>". PHP_EOL;
            }
        }
        
        if(null !== $reports){
            foreach ($reports as $report){
                $articlesXml .= "<url><loc>http://{$host}/reportaze/r/{$report->getUrlKey()}</loc></url>". PHP_EOL;
            }
        }

        file_put_contents('./sitemap.xml', $xml . $pageContentXml . $articlesXml. $xmlEnd);

        Event::fire('admin.log', array('success'));
        $view->successMessage('Soubor sitemap.xml byl aktualizován');
        self::redirect('/admin/system/');
    }

    /**
     * Fill database tables tb_action, tb_news and tb_report with testing data
     * For database filling use these urls:
     *      /admin/system/filldatabase/1    - for tb_news
     *      /admin/system/filldatabase/2    - for tb_action
     *      /admin/system/filldatabase/3    - for tb_report
     * 
     * @before _secured, _superadmin
     */
    public function fillDatabase($type)
    {
        if (ENV !== 'dev') {
            exit;
        }

        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;

        ini_set('max_execution_time', 1800);
        ini_set('memory_limit', '256M');

        $ROW_COUNT = 50;

        $content = \App\Model\PageContentModel::first(array('urlKey = ?' => 'kurzy-sdi'), array('body'));

        $SHORT_TEXT = 'Vedle používání zdravého rozumu, dostatečné kvalifikace i praxe je kvalitní a spolehlivá 
            potápěčská technika jednou z podmínek dosažení nejvyšší míry bezpečnosti vašich ponorů. 
            Kupujte jen takovou výstroj, která tato kriteria splňuje! Pamatujte, že cena je až 
            druhotným ukazatelem ... nebo váš život stojí za pár ušetřených stokorun?';

        $LARGE_TEXT = str_replace('h1','h2',$content->getBody());
        unset($content);

        $META_DESC = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse efficitur viverra libero, at dapibus sapien placerat a. '
                . 'In efficitur tortor in nulla auctor tristique. Pellentesque non nisi mollis, tincidunt purus rutrum, ornare sem.';

        if ((int) $type == 1) {
            for ($i = 0; $i < $ROW_COUNT; $i++) {
                $news = new \App\Model\NewsModel(array(
                    'title' => 'News-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'news-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'keywords' => 'news',
                    'metaTitle' => 'News-' . $i . '-' . time(),
                    'metaDescription' => $META_DESC
                ));

                $news->save();
                unset($news);
            }
            self::redirect('/admin/system/');
        }

        if ((int) $type == 2) {
            for ($i = 0; $i < $ROW_COUNT; $i++) {
                $date = new \DateTime();
                $date->add(new \DateInterval('P' . (int) $i . 'D'));
                $startDate = $date->format('Y-m-d');
            
                $action = new \App\Model\ActionModel(array(
                    'title' => 'Action-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'action-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'startDate' => $startDate,
                    'endDate' => $startDate,
                    'startTime' => '',
                    'endTime' => '',
                    'keywords' => 'action',
                    'metaTitle' => 'Action-' . $i . '-' . time(),
                    'metaDescription' => $META_DESC
                ));

                $action->save();
                unset($action);
            }
            self::redirect('/admin/system/');
        }

        if ((int) $type == 3) {
            for ($i = 0; $i < $ROW_COUNT; $i++) {
                $report = new \App\Model\ReportModel(array(
                    'title' => 'Report-' . $i . '-' . time(),
                    'userId' => 1,
                    'userAlias' => 'System',
                    'urlKey' => 'report-' . $i . '-' . time(),
                    'approved' => 1,
                    'archive' => 0,
                    'shortBody' => $SHORT_TEXT,
                    'body' => $LARGE_TEXT,
                    'rank' => 1,
                    'keywords' => 'report',
                    'metaTitle' => 'Report-' . $i . '-' . time(),
                    'metaDescription' => $META_DESC,
                    'metaImage' => '',
                    'photoName' => '',
                    'imgMain' => '',
                    'imgThumb' => ''
                ));

                $report->save();
                unset($report);
            }
            self::redirect('/admin/system/');
        }
    }

    /**
     * @before _secured, _superadmin
     */
    public function linecounter()
    {
        $view = $this->getActionView();
        
        $counter = new LineCounter();
        $totalLines = $counter->countLines(APP_PATH);
        $fileCounter = $counter->getFileCounter();
        
        $view->set('totallines', $totalLines)
                ->set('filecounter', $fileCounter);
    }
}
