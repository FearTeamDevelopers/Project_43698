<?php

namespace Admin\Controller;

use Admin\Etc\Controller;
use Admin\Model\AdminLogModel;
use Admin\Model\SitemapModel;
use Exception;
use PHPWee\Minify;
use THCFrame\Configuration\Model\ConfigModel;
use THCFrame\Core\Core;
use THCFrame\Database\Mysqldump;
use THCFrame\Events\Events as Event;
use THCFrame\Filesystem\LineCounter;
use THCFrame\Model\Exception\Connector;
use THCFrame\Model\Exception\Implementation;
use THCFrame\Model\Generator;
use THCFrame\Profiler\Profiler;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\View\Exception\Data;

/**
 *
 */
class SystemController extends Controller
{

    /**
     * @before _secured, _admin
     */
    public function index(): void
    {

    }

    /**
     * Ability to clear cache from administration.
     *
     * @before _secured, _admin
     */
    public function common(): void
    {
        $this->disableView();
        $view = $this->getActionView();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            self::redirect('/admin/system/');
        }

        if (RequestMethods::post('clearCache')) {
            Event::fire('admin.log', ['success']);
            $this->getCache()->clearCache();

            $view->successMessage($this->lang('SYSTEM_DELETE_CACHE'));
            self::redirect('/admin/system/');
        }

        if (RequestMethods::post('minifyJs')) {
            $jsPath = APP_PATH . '/public/js/custom';

            foreach (glob($jsPath . '/*.js') as $file) {
                $content = file_get_contents($file);
                $fileName = basename($file, '.js');
                $minified = Minify::js($content);
                file_put_contents($jsPath . '/build/' . $fileName . '.js', $minified);
            }

            $view->successMessage('JS resources have been sucessfully minified');
            self::redirect('/admin/system/');
        }
    }

    /**
     * Create db bakcup.
     *
     * @before _secured, _admin
     */
    public function createDatabaseBackup(): void
    {
        $view = $this->getActionView();
        $dump = new Mysqldump();

        if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
            self::redirect('/admin/system/');
        }

        try {
            if ($dump->create()) {
                $view->successMessage($this->lang('SYSTEM_DB_BACKUP'));
                Event::fire('admin.log', ['success', 'Database backup']);
            } else {
                $view->errorMessage($this->lang('SYSTEM_DB_BACKUP_FAIL'));
                Event::fire('admin.log', ['fail', 'Database backup']);
            }
        } catch (\THCFrame\Database\Exception\Mysqldump $ex) {
            $view->errorMessage($ex->getMessage());
            Event::fire('admin.log', [
                'fail',
                'Database backup',
                'Error: ' . $ex->getMessage(),
            ]);
        }

        self::redirect('/admin/system/');
    }

    /**
     * Get admin log.
     *
     * @before _secured, _superadmin
     * @throws Connector
     * @throws Implementation
     * @throws Data
     */
    public function showLog(): void
    {
        $view = $this->getActionView();
        $log = AdminLogModel::all([], ['*'], ['created' => 'DESC'], 500);
        $view->set('adminlog', $log);
    }

    /**
     *
     * @param int $id
     * @throws Connector
     * @throws Implementation
     * @before _secured, _superadmin
     */
    public function showLogDetail($id): void
    {
        $this->disableView();

        $log = AdminLogModel::first(['id = ?' => (int)$id]);

        if (!empty($log)) {
            $params = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', static function ($match) {
                return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
            }, $log->getParams());

            $str = 'Akce: <br/><strong>' . $log->getModule() . '/' . $log->getController() . '/' . $log->getAction() . '</strong><br/><br/>';
            $str .= 'Referer: <br/><strong>' . $log->getHttpreferer() . '</strong><br/><br/>';
            $str .= 'Parametry: <br/><strong>' . $params . '</strong>';
            echo $str;
        } else {
            $this->ajaxResponse($this->lang('NOT_FOUND'), true, 404);
        }
    }

    /**
     * Edit application settings.
     *
     * @before _secured, _admin
     * @throws Connector
     * @throws Implementation
     * @throws Data
     */
    public function settings(): void
    {
        $view = $this->getActionView();
        $config = ConfigModel::all([], ['*'], ['title' => 'ASC']);
        $view->set('config', $config);

        if (RequestMethods::post('submitEditSet')) {
            if ($this->getSecurity()->getCsrf()->verifyRequest() !== true) {
                self::redirect('/admin/');
            }
            $errors = [];

            foreach ($config as $conf) {
                $oldVal = $conf->getValue();
                $conf->value = RequestMethods::post($conf->getXkey());

                if ($conf->validate()) {
                    Event::fire('admin.log',
                        ['success', $conf->getXkey() . ': ' . $oldVal . ' - ' . $conf->getValue()]);
                    $conf->save();
                } else {
                    Event::fire('admin.log', ['fail', $conf->getXkey() . ': ' . json_encode($conf->getErrors())]);
                    $error = $conf->getErrors();
                    $errors[$conf->xkey] = array_shift($error);
                }
            }

            if (empty($errors)) {
                $view->successMessage($this->lang('UPDATE_SUCCESS'));
                self::redirect('/admin/system/');
            } else {
                $view->set('errors', $errors);
            }
        }
    }

    /**
     * Get profiler result.
     *
     * @before _secured
     */
    public function showProfiler(): void
    {
        $this->disableView();

        echo Profiler::display();
    }

    /**
     * Generate sitemap.xml.
     *
     * @before _secured, _admin
     */
    public function generateSitemap(): void
    {
        $view = $this->getActionView();

        $linkCounter = SitemapModel::generateSitemap();

        Event::fire('admin.log', ['success', 'Links count: ' . $linkCounter]);
        $view->successMessage('Soubor sitemap.xml byl aktualizován');
        self::redirect('/admin/system/');
    }

    /**
     * @before _secured, _superadmin
     * @throws Data
     * @throws Data
     */
    public function linecounter(): void
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
     * @before _secured, _superadmin
     * @param string $dbIdent
     */
    public function generator($dbIdent = 'main'): void
    {
        $this->disableView();
        $view = $this->getActionView();

        try {
            $generator = new Generator(['dbIdent' => $dbIdent]);
            $generator->createModels();

            Event::fire('admin.log', ['success', 'Generate model classes']);
            $view->successMessage('New models were generated');
            self::redirect('/admin/system/');
        } catch (Exception $ex) {
            Event::fire('admin.log', ['fail', 'An error occured while creating model classes: ' . $ex->getMessage()]);
            $view->errorMessage('An error occured while creating model classes: ' . $ex->getMessage());
            self::redirect('/admin/system/');
        }
    }

    /**
     * @before _secured, _superadmin
     * @param int $type
     * @throws \THCFrame\Database\Exception\Mysqldump
     */
    public function sync($type = 1): void
    {
        //set_time_limit(0);
        $this->disableView();
        $view = $this->getActionView();

        //TODO: get registered modules and go throught module\model\basic directory
        $models = [];

        $db = Registry::get('database')->get();
        $error = false;
        $executeQuery = false;

        $dump = new Mysqldump();
        $dump->create();

        foreach ($models as $model) {
            $m = new $model();

            if ($type == 1) {
                if (!$db->sync($m, $executeQuery, 'create', true)) {
                    $errMsg = 'An error occured while executing db sync for model: ' . $model . '. Check error log';
                    $error = true;
                }
            } elseif ($type == 2) {
                if (!$db->sync($m, $executeQuery, 'alter', false)) {
                    $errMsg = 'An error occured while executing db sync for model: ' . $model . '. Check error log';
                    $error = true;
                }
            }

            unset($m);
        }

        if ($error === true) {
            Event::fire('admin.log', ['fail', $errMsg]);
            Core::getLogger()->error($errMsg);
            $view->errorMessage('An error occured while executing db sync. Check error log');
            self::redirect('/admin/system/');
        } else {
            Event::fire('admin.log', ['success', 'Model -> DB sync']);
            $view->successMessage('Model -> DB sync done');
            self::redirect('/admin/system/');
        }
    }
}
