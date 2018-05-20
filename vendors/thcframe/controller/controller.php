<?php

namespace THCFrame\Controller;

use THCFrame\Cache\Cache;
use THCFrame\Configuration\Configuration;
use THCFrame\Controller\Exception\Model;
use THCFrame\Profiler\Profiler;
use THCFrame\Security\Security;
use THCFrame\Session\Driver;
use THCFrame\View\Exception as ViewException;
use THCFrame\Core\Base;
use THCFrame\View\View;
use THCFrame\Events\Events as Event;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Request\Response;
use THCFrame\Core\Lang;

/**
 * Parent controller class
 */
class Controller extends Base
{

    /**
     * Controller name
     *
     * @read
     * @var string
     */
    protected $name;

    /**
     * @readwrite
     */
    protected $parameters;

    /**
     * @readwrite
     */
    protected $layoutView;

    /**
     * @readwrite
     */
    protected $actionView;

    /**
     * @readwrite
     */
    protected $willRenderLayoutView = true;

    /**
     * @readwrite
     */
    protected $willRenderActionView = true;

    /**
     * @readwrite
     */
    protected $defaultPath = 'modules/%s/view';

    /**
     * @readwrite
     */
    protected $defaultLayout = 'layouts/basic';

    /**
     * @readwrite
     */
    protected $mobileLayout;

    /**
     * @readwrite
     */
    protected $tabletLayout;

    /**
     * @readwrite
     */
    protected $defaultExtension = ['phtml', 'html'];

    /**
     * @readwrite
     */
    protected $defaultContentType = 'text/html';

    /**
     * Store device type from Mobile Detect class
     *
     * @var string
     * @read
     */
    protected $deviceType;

    /**
     * Response object
     *
     * @read
     * @var Response
     */
    protected $response;

    /**
     * Store security context object.
     *
     * @var Security
     * @read
     */
    protected $security;

    /**
     * Store initialized cache object.
     *
     * @var Cache
     * @read
     */
    protected $cache;

    /**
     * Store configuration.
     *
     * @var Configuration
     * @read
     */
    protected $config;

    /**
     * Store language extension.
     *
     * @var Lang
     * @read
     */
    protected $lang;

    /**
     * Store server host name.
     *
     * @var string
     * @read
     */
    protected $serverHost;

    /**
     * Session object
     *
     * @read
     * @var Driver
     */
    protected $session;
    protected $sessionToken;

    /**
     * @param string $method
     * @return \THCFrame\Core\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     *
     */
    protected function mutliSubmissionProtectionToken()
    {
        $token = $this->session->get('submissionprotection');

        if ($token === null) {
            $token = md5(microtime());
            $this->session->set('submissionprotection', $token);
        }

        return $token;
    }

    /**
     *
     * @return string
     */
    protected function revalidateMultiSubmissionProtectionToken()
    {
        $this->session->remove('submissionprotection');
        $token = md5(microtime());
        $this->session->set('submissionprotection', $token);

        return $token;
    }

    /**
     * @return bool
     */
    protected function checkMultiSubmissionProtectionToken()
    {
        $this->sessionToken = $this->session->get('submissionprotection');

        $token = RequestMethods::post('submstoken');

        if ($token == $this->sessionToken) {
            $this->session->remove('submissionprotection');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Dodatecna ochrana pred spambotama
     *
     * @return bool
     */
    protected function checkBrowserAgentAndReferer()
    {
        return RequestMethods::server('HTTP_USER_AGENT') == '' || RequestMethods::server('HTTP_REFERER') == '';
    }

    /**
     * @param string $message
     * @param bool $error
     * @param int $status
     * @param array $additionalData
     */
    protected function ajaxResponse($message, $error = false, $status = 200, array $additionalData = [])
    {
        $data = [
                'message' => $message,
                'error' => (bool)$error,
                'csrf' => $this->getSecurity()->getCsrf()->getToken(),
            ] + $additionalData;

        $this->response->setHttpVersionStatusHeader('HTTP/1.1 ' . (int)$status . ' ' . $this->response->getStatusMessageByCode($status))
            ->setHeader('Content-type', 'application/json')
            ->setData($data);

        $this->response->sendHeaders();
        $this->response->send();
    }

    /**
     * Static function for redirects
     *
     * @param string $url
     */
    public static function redirect($url = null)
    {
        $schema = 'http';
        $host = RequestMethods::server('HTTP_HOST');

        if ($url === null) {
            header("Location: {$schema}://{$host}");
            exit;
        } else {
            header("Location: {$schema}://{$host}{$url}");
            exit;
        }
    }

    /**
     * Controller constructor.
     * @param array $options
     * @throws \Exception
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        Event::fire('framework.controller.construct.before', [$this->name]);

        //get resources
        $configuration = Registry::get('configuration');
        $router = Registry::get('router');

        $this->response = new Response();
        $this->session = Registry::get('session');
        $this->security = Registry::get('security');
        $this->serverHost = RequestMethods::server('HTTP_HOST');
        $this->cache = Registry::get('cache');
        $this->config = Registry::get('configuration');
        $this->lang = Lang::getInstance();

        if (!empty($configuration->view)) {
            $this->defaultExtension = explode(',', $configuration->view->extension);
            $this->defaultLayout = $configuration->view->layout;
            $this->mobileLayout = $configuration->view->mobileLayout;
            $this->tabletLayout = $configuration->view->tabletLayout;
            $this->defaultPath = $configuration->view->path;
        } else {
            throw new \Exception('Error in configuration file');
        }

        //collect main variables
        $module = $router->getLastRoute()->getModule();
        $controller = $router->getLastRoute()->getController();
        $action = $router->getLastRoute()->getAction();

        $deviceType = $this->getDeviceType();

        if ($deviceType == 'phone' && $this->mobileLayout != '') {
            $defaultLayout = $this->mobileLayout;
        } elseif ($deviceType == 'tablet' && $this->tabletLayout != '') {
            $defaultLayout = $this->tabletLayout;
        } else {
            $defaultLayout = $this->defaultLayout;
        }

        $defaultPath = sprintf($this->defaultPath, $module);

        //create view instances
        if ($this->willRenderLayoutView) {
            foreach ($this->defaultExtension as $ext) {
                if (file_exists(APP_PATH . "/{$defaultPath}/{$defaultLayout}.{$ext}")) {
                    $viewFile = APP_PATH . "/{$defaultPath}/{$defaultLayout}.{$ext}";
                    break;
                }
            }

            $view = new View([
                'file' => $viewFile
            ]);

            $this->layoutView = $view;
        }

        if ($this->willRenderActionView) {
            foreach ($this->defaultExtension as $ext) {
                if (file_exists(APP_PATH . "/{$defaultPath}/{$controller}/{$action}.{$ext}")) {
                    $viewFile = APP_PATH . "/{$defaultPath}/{$controller}/{$action}.{$ext}";
                    break;
                }
            }

            $view = new View([
                'file' => $viewFile
            ]);

            $this->actionView = $view;
        }

        Event::fire('framework.controller.construct.after', [$this->name]);
    }

    /**
     * Object destruct
     */
    /**
     * @throws ViewException\Renderer
     */
    public function __destruct()
    {
        Event::fire('framework.controller.destruct.before', [$this->name]);

        $this->render();

        Event::fire('framework.controller.destruct.after', [$this->name]);
    }

    /**
     * Return action view
     *
     * @return View
     */
    public function getActionView()
    {
        return $this->actionView;
    }

    /**
     * Return layout view
     *
     * @return View
     */
    public function getLayoutView()
    {
        return $this->layoutView;
    }

    /**
     * Return server url with http schema
     *
     * @return string
     */
    public function getServerHost()
    {
        return RequestMethods::getServerHost();
    }

    /**
     * Return model instance
     *
     * @param string $model Format: module/model_name
     * @param null $options
     * @return mixed
     * @throws Model
     */
    public function getModel($model, $options = null)
    {
        list($module, $modelName) = explode('/', $model);

        if ($module == '' || $modelName == '') {
            throw new Model(sprintf('%s is not valid model name', $model));
        } else {
            $fileName = APP_PATH . strtolower("/modules/{$module}/model/{$modelName}.php");
            $className = ucfirst($module) . '_Model_' . ucfirst($modelName);

            if (file_exists($fileName)) {
                if (null !== $options) {
                    return new $className($options);
                } else {
                    return new $className();
                }
            }
        }
    }

    /**
     * Return device type string
     *
     * @return string
     */
    public function getDeviceType()
    {
        $detect = Registry::get('mobiledetect');

        $deviceType = $this->session->get('deviceType');

        if ($deviceType === null) {
            if ($detect->isMobile() && !$detect->isTablet()) {
                $deviceType = 'phone';
            } elseif ($detect->isTablet() && !$detect->isMobile()) {
                $deviceType = 'tablet';
            } else {
                $deviceType = 'computer';
            }

            $this->session->set('deviceType', $deviceType);
        }

        return $deviceType;
    }

    /**
     * Main render method
     *
     * @throws ViewException\Renderer
     */
    public function render()
    {
        Event::fire('framework.controller.render.before', [$this->name]);

        session_write_close();

        $defaultContentType = $this->defaultContentType;
        $results = null;

        $doAction = $this->willRenderActionView && $this->actionView;
        $doLayout = $this->willRenderLayoutView && $this->layoutView;
        $profiler = Profiler::getInstance();

        try {
            if ($doAction) {
                $results = $this->actionView->render();

                $this->actionView
                    ->template
                    ->implementation
                    ->set('action', $results);
            }

            if ($doLayout) {
                $results = $this->layoutView->render();

                //protection against clickjacking and xss
                $this->response->setHeader('X-Frame-Options', 'deny')
                    ->setHeader('X-XSS-Protection', '1; mode=block')
                    ->setHeader('Content-type', $defaultContentType)
                    ->setBody($results);

                $profiler->stop();
                $this->response->sendHeaders()
                    ->send(false);
            } elseif ($doAction) {

                //protection against clickjacking and xss
                $this->response->setHeader('X-Frame-Options', 'deny')
                    ->setHeader('X-XSS-Protection', '1; mode=block')
                    ->setHeader('Content-type', $defaultContentType)
                    ->setBody($results);

                $profiler->stop();
                $this->response->sendHeaders()
                    ->send(false);
            }

            $this->willRenderLayoutView = false;
            $this->willRenderActionView = false;
        } catch (\Exception $e) {
            throw new ViewException\Renderer('Invalid layout/template syntax');
        }

        Event::fire('framework.controller.render.after', [$this->name]);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function getWillRenderLayoutView()
    {
        return $this->willRenderLayoutView;
    }

    public function getWillRenderActionView()
    {
        return $this->willRenderActionView;
    }

    public function getDefaultPath()
    {
        return $this->defaultPath;
    }

    public function getDefaultLayout()
    {
        return $this->defaultLayout;
    }

    public function getMobileLayout()
    {
        return $this->mobileLayout;
    }

    public function getTabletLayout()
    {
        return $this->tabletLayout;
    }

    public function getDefaultExtension()
    {
        return $this->defaultExtension;
    }

    public function getDefaultContentType()
    {
        return $this->defaultContentType;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getSecurity()
    {
        return $this->security;
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getLang()
    {
        return $this->lang;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function setWillRenderLayoutView($willRenderLayoutView)
    {
        $this->willRenderLayoutView = $willRenderLayoutView;
        return $this;
    }

    public function setWillRenderActionView($willRenderActionView)
    {
        $this->willRenderActionView = $willRenderActionView;
        return $this;
    }

    public function setDefaultPath($defaultPath)
    {
        $this->defaultPath = $defaultPath;
        return $this;
    }

    public function setDefaultLayout($defaultLayout)
    {
        $this->defaultLayout = $defaultLayout;
        return $this;
    }

    public function setMobileLayout($mobileLayout)
    {
        $this->mobileLayout = $mobileLayout;
        return $this;
    }

    public function setTabletLayout($tabletLayout)
    {
        $this->tabletLayout = $tabletLayout;
        return $this;
    }

    public function setDefaultExtension($defaultExtension)
    {
        $this->defaultExtension = $defaultExtension;
        return $this;
    }

    public function setDefaultContentType($defaultContentType)
    {
        $this->defaultContentType = $defaultContentType;
        return $this;
    }

    public function setResponse(Response $response)
    {
        $this->response = $response;
        return $this;
    }

    public function setSecurity(Security $security)
    {
        $this->security = $security;
        return $this;
    }

    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function setConfig(Configuration $config)
    {
        $this->config = $config;
        return $this;
    }

    public function setLang(Lang $lang)
    {
        $this->lang = $lang;
        return $this;
    }

    public function setSession(Driver $session)
    {
        $this->session = $session;
        return $this;
    }

    public function getSessionToken()
    {
        return $this->sessionToken;
    }

    public function setSessionToken($sessionToken)
    {
        $this->sessionToken = $sessionToken;
        return $this;
    }

}
