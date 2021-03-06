<?php

namespace THCFrame\Core;

use THCFrame\Cache\Cache;
use THCFrame\Configuration\Configuration;
use THCFrame\Core\Exception as Exception;
use THCFrame\Database\Database;
use THCFrame\Logger\Driver;
use THCFrame\Logger\Logger;
use THCFrame\Logger\LoggerComposite;
use THCFrame\Logger\LoggerInterface;
use THCFrame\Module\Exception\Multiload;
use THCFrame\Module\Exception\NoModuleToRegister;
use THCFrame\Registry\Registry;
use THCFrame\Router\Dispatcher;
use THCFrame\Router\Router;
use THCFrame\Security\Security;
use THCFrame\Session\Session;
use Throwable;

/**
 * THCFrame core class
 */
class Core
{

    CONST ENV_DEV = 'dev';
    CONST ENV_TEST = 'test';
    CONST ENV_QA = 'qa';
    CONST ENV_LIVE = 'live';

    /**
     * Logger instance
     *
     * @var Logger
     */
    private static $logger;

    /**
     * Autoloader instance
     *
     * @var Autoloader
     */
    private static $autoloader;

    /**
     * Registered modules
     *
     * @var array
     */
    private static $modules = [];

    /**
     * List of exceptions
     *
     * @var array
     */
    private static $exceptions = [
        '401' => [
            'THCFrame\Security\Exception\Role',
            'THCFrame\Security\Exception\Unauthorized',
            'THCFrame\Security\Exception\UserExpired',
            'THCFrame\Security\Exception\UserInactive',
            'THCFrame\Security\Exception\UserPassExpired',
            'THCFrame\Security\Exception\CSRF',
            'THCFrame\Security\Exception\WrongPassword',
            'THCFrame\Security\Exception\UserNotExists',
            'THCFrame\Security\Exception\BruteForceAttack',
            'THCFrame\Security\Exception\SessionFixationAttack',
        ],
        '404' => [
            'THCFrame\Router\Exception\Module',
            'THCFrame\Router\Exception\Action',
            'THCFrame\Router\Exception\Controller'
        ],
        '500' => [
            'THCFrame\Cache\Exception',
            'THCFrame\Cache\Exception\Argument',
            'THCFrame\Cache\Exception\Implementation',
            'THCFrame\Configuration\Exception',
            'THCFrame\Configuration\Exception\Argument',
            'THCFrame\Configuration\Exception\Implementation',
            'THCFrame\Configuration\Exception\Syntax',
            'THCFrame\Controller\Exception',
            'THCFrame\Controller\Exception\Argument',
            'THCFrame\Controller\Exception\Implementation',
            'THCFrame\Controller\Exception\Header',
            'THCFrame\Core\Exception',
            'THCFrame\Core\Exception\Argument',
            'THCFrame\Core\Exception\Implementation',
            'THCFrame\Core\Exception\Property',
            'THCFrame\Core\Exception\ReadOnly',
            'THCFrame\Core\Exception\WriteOnly',
            'THCFrame\Database\Exception',
            'THCFrame\Database\Exception\Argument',
            'THCFrame\Database\Exception\Implementation',
            'THCFrame\Database\Exception\Sql',
            'THCFrame\Logger\Exception',
            'THCFrame\Logger\Exception\Argument',
            'THCFrame\Logger\Exception\Implementation',
            'THCFrame\Model\Exception',
            'THCFrame\Model\Exception\Argument',
            'THCFrame\Model\Exception\Connector',
            'THCFrame\Model\Exception\Implementation',
            'THCFrame\Model\Exception\Primary',
            'THCFrame\Model\Exception\Type',
            'THCFrame\Model\Exception\Validation',
            'THCFrame\Module\Exception\Multiload',
            'THCFrame\Module\Exception\NoModuleToRegister',
            'THCFrame\Module\Exception\Implementation',
            'THCFrame\Module\Exception',
            'THCFrame\Profiler\Exception',
            'THCFrame\Profiler\Exception\Disabled',
            'THCFrame\Request\Exception',
            'THCFrame\Request\Exception\Argument',
            'THCFrame\Request\Exception\Implementation',
            'THCFrame\Request\Exception\Response',
            'THCFrame\Router\Exception',
            'THCFrame\Router\Exception\Argument',
            'THCFrame\Router\Exception\Implementation',
            'THCFrame\Rss\Exception',
            'THCFrame\Rss\Exception\InvalidDetail',
            'THCFrame\Rss\Exception\InvalidItem',
            'THCFrame\Security\Exception',
            'THCFrame\Security\Exception\Implementation',
            'THCFrame\Security\Exception\HashAlgorithm',
            'THCFrame\Session\Exception',
            'THCFrame\Session\Exception\Argument',
            'THCFrame\Session\Exception\Implementation',
            'THCFrame\Template\Exception',
            'THCFrame\Template\Exception\Argument',
            'THCFrame\Template\Exception\Implementation',
            'THCFrame\Template\Exception\Parser',
            'THCFrame\View\Exception',
            'THCFrame\View\Exception\Argument',
            'THCFrame\View\Exception\Data',
            'THCFrame\View\Exception\Implementation',
            'THCFrame\View\Exception\Renderer',
            'THCFrame\View\Exception\Syntax'
        ],
        '503' => [
            'THCFrame\Database\Exception\Service',
            'THCFrame\Configuration\Exception\Smtp',
            'THCFrame\Cache\Exception\Service'
        ],
        '507' => [
            'THCFrame\Router\Exception\Offline'
        ]
    ];

    private function __construct()
    {

    }

    private function __clone()
    {

    }

    /**
     * @param $array
     * @return array|string
     */
    private static function clean($array)
    {
        if (is_array($array)) {
            return array_map(__CLASS__ . '::_clean', $array);
        }
        return htmlentities(trim($array), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Error handler
     *
     * @param type $number
     * @param type $text
     * @param type $file
     * @param type $row
     */
    public static function errorHandler($number, $text, $file, $row)
    {
        switch ($number) {
            case E_WARNING:
            case E_USER_WARNING :
                $type = Driver::WARNING;
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $type = Driver::NOTICE;
                break;
            default:
                $type = Driver::ERROR;
                break;
        }

        if (self::$logger instanceof LoggerInterface) {
            self::$logger->log($type, '[{file}:{row}] [{text}]', ['file' => $file, 'row' => $row, 'text' => $text]);
        } else {
            file_put_contents(APP_PATH . '/application/logs/error.log', "{$type} ~ {$file} ~ {$row} ~ {$text}" . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Exception handler
     *
     * @param  $exception
     */
    public static function exceptionHandler($exception)
    {

        if (self::$logger instanceof LoggerInterface) {
            self::$logger->error('Uncaught exception: {exception}', ['exception' => $exception]);
        } else {
            $type = get_class($exception);
            file_put_contents(APP_PATH . '/application/logs/exception.log', "Uncaught exception: {$type} ~ {$exception->getFile()} ~ {$exception->getLine()} ~ {$exception->getMessage()}" . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Generates new application secret which is used is hashing
     * functions. Can be used only in dev env
     *
     * @return string
     */
    public static function generateSecret()
    {
        if (ENV == self::ENV_DEV) {
            return substr(rtrim(base64_encode(md5(microtime())), "="), 5, 25);
        } else {
            return null;
        }
    }

    /**
     * Return logger instance
     *
     * @return Logger
     */
    public static function getLogger()
    {
        return self::$logger;
    }

    /**
     * Main framework initialization method
     *
     * @param array $modules
     * @param array $autoloaderPrefixes
     * @return void
     * @throws \THCFrame\Core\Exception
     */
    public static function initialize(array $modules = [], $autoloaderPrefixes = [])
    {
        if (!defined('APP_PATH')) {
            throw new Exception('APP_PATH not defined');
        }

        // fix extra backslashes in $_POST/$_GET
        if (get_magic_quotes_gpc()) {
            $globals = ['_POST', '_GET', '_COOKIE', '_REQUEST', '_SESSION'];

            foreach ($globals as $global) {
                if (isset($GLOBALS[$global])) {
                    $GLOBALS[$global] = self::clean($GLOBALS[$global]);
                }
            }
        }

        // Autoloader
        require_once APP_PATH . '/vendors/thcframe/core/autoloader.php';
        self::$autoloader = new Autoloader();
        self::$autoloader->register();

        if (!empty($autoloaderPrefixes)) {
            self::$autoloader->addNamespaces($autoloaderPrefixes);
        }

        try {
            // Logger
            $loggerComposite = new LoggerComposite();
            $fileLogger = new Logger(['type' => 'file']);
            $loggerComposite->addChild($fileLogger->initialize(), 'file');
            self::$logger = $loggerComposite;

            // error and exception handlers
            set_error_handler(__CLASS__ . '::errorHandler');
            set_exception_handler(__CLASS__ . '::exceptionHandler');

            //register modules
            if (empty($modules)) {
                $modules = self::loadModules();
            }
            self::registerModules($modules);

            // configuration
            $configuration = new Configuration(
                    ['type' => 'ini', 'options' => ['env' => ENV]]
            );
            $confingInitialized = $configuration->initialize();
            $parsedConfig = $confingInitialized->getConfiguration();
            Registry::set('configuration', $parsedConfig);

            // database
            if ($parsedConfig->database->main->host != '') {
                $database = new Database();
                $connectors = $database->initialize($parsedConfig);
                Registry::set('database', $connectors);

                //extend configuration for config loaded from db
                $confingInitialized->extendForDbConfig();
                Registry::set('configuration', $confingInitialized->getConfiguration());
            }

            // cache
            $cache = new Cache();
            Registry::set('cache', $cache->initialize($parsedConfig));

            // session
            $session = new Session();
            Registry::set('session', $session->initialize($parsedConfig));

            // security
            $security = new Security();
            Registry::set('security', $security->initialize($parsedConfig));

            // unset globals
            unset($configuration);
            unset($parsedConfig);
            unset($database);
            unset($cache);
            unset($session);
            unset($security);
        } catch (Throwable $e) {
            self::renderFallbackTemplate($e);
        } catch (\Exception $e) {
            self::renderFallbackTemplate($e);
        }
    }

    /**
     * Load every module in modules dir
     *
     * @return array array of module names
     * @throws NoModuleToRegister
     */
    public static function loadModules()
    {
        $dirContent = array_diff(scandir(MODULES_PATH), ['.', '..']);
        $modules = [];

        if (!empty($dirContent)) {
            foreach ($dirContent as $item) {
                if (is_dir(MODULES_PATH . DIRECTORY_SEPARATOR . $item)) {
                    $modules[] = strtolower($item);
                }
            }

            return $modules;
        } else {
            throw new NoModuleToRegister('No modules to load');
        }
    }

    /**
     * Register new modules within application.
     * As parameter is given an array with module names
     *
     * @param array $moduleArray
     * @throws Multiload
     * @throws NoModuleToRegister
     */
    public static function registerModules(array $moduleArray = [])
    {
        if (!empty($moduleArray)) {
            foreach ($moduleArray as $moduleName) {
                self::registerModule($moduleName);
            }
        } else {
            throw new NoModuleToRegister('No modules to load');
        }
    }

    /**
     * Register single module based on provided module name.
     * Module instance is created and stored in _modules array
     *
     * @throws Multiload
     */
    public static function registerModule($moduleName)
    {
        if (array_key_exists(ucfirst($moduleName), self::$modules)) {
            throw new Multiload(sprintf('Module %s has been alerady loaded', ucfirst($moduleName)));
        } else {
            self::$autoloader->addNamespace(ucfirst($moduleName), MODULES_PATH . DIRECTORY_SEPARATOR . strtolower($moduleName));
            $moduleClass = ucfirst($moduleName) . "\Etc\ModuleConfig";

            $moduleObject = new $moduleClass();
            $moduleObjectName = ucfirst($moduleObject->getModuleName());
            self::$modules[$moduleObjectName] = $moduleObject;
        }
    }

    /**
     * Return instance of registered module based on provided module name
     *
     * @param string $moduleName
     * @return null | THCFrame\Module\Module
     */
    public static function getModule($moduleName)
    {
        $moduleName = ucfirst($moduleName);

        if (array_key_exists($moduleName, self::$modules)) {
            return self::$modules[$moduleName];
        } else {
            return null;
        }
    }

    /**
     * Return array with registered modules
     *
     * @return null | array
     */
    public static function getModules()
    {
        if (empty(self::$modules)) {
            return null;
        } else {
            return self::$modules;
        }
    }

    /**
     * Return registered module names
     *
     * @return null | array
     */
    public static function getModuleNames($nameToLower = false)
    {
        if (empty(self::$modules)) {
            return null;
        } else {
            $moduleNames = [];

            foreach (self::$modules as $module) {
                if ($nameToLower === true) {
                    $moduleNames[] = strtolower($module->getModuleName());
                } else {
                    $moduleNames[] = $module->getModuleName();
                }
            }

            return $moduleNames;
        }
    }

    /**
     * Initialize router and dispatcher and dispatch request.
     * If there is some error method tries to find and render error template
     */
    public static function run()
    {
        try {
            //router
            $router = new Router([
                'url' => urldecode($_SERVER['REQUEST_URI'])
            ]);
            Registry::set('router', $router);

            //dispatcher
            $dispatcher = new Dispatcher();
            Registry::set('dispatcher', $dispatcher->initialize());

            $dispatcher->dispatch($router->getLastRoute());

            unset($dispatcher);
        } catch (Throwable $e) {
            $isApi = false;
            if (isset($router) && stripos($router->getUrl(), '/api/') !== false) {
                $isApi = true;
            }

            self::renderFallbackTemplate($e, $isApi);
        } catch (\Exception $e) {
            $isApi = false;
            if (isset($router) && stripos($router->getUrl(), '/api/') !== false) {
                $isApi = true;
            }

            self::renderFallbackTemplate($e, $isApi);
        }
    }

    /**
     * Return framework version
     *
     * @return string
     */
    public static function getFrameworkVersion()
    {
        return '1.3.0';
    }

    private static function renderFallbackTemplate($exception, $isApi = false)
    {
        $exceptionClass = get_class($exception);

        // attempt to find the approapriate error template, and render
        foreach (self::$exceptions as $template => $classes) {
            foreach ($classes as $class) {
                if ($class == $exceptionClass) {
                    $controller = Registry::get('controller');

                    if (null !== $controller) {
                        $controller->willRenderLayoutView = false;
                        $controller->willRenderActionView = false;
                    }

                    $defaultErrorFile = MODULES_PATH . "/app/view/errors/{$template}.phtml";

                    http_response_code($template);

                    if ($isApi) {
                        header('Content-Type: application/json');
                        echo json_encode(['code' => $template]);
                    } else {
                        header('Content-Type: text/html');
                        include($defaultErrorFile);
                    }
                    exit();
                }
            }
        }

        // render fallback template
        self::$logger->error('{exception}', ['exception' => $exception]);

        http_response_code(500);
        header('Content-type: text/html');
        echo 'An error occurred.';
        if (ENV == self::ENV_DEV) {
            print ('<pre>' . print_r($exception, true) . '</pre>');
        }
        exit;
    }

}
