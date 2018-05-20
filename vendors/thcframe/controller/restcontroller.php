<?php

namespace THCFrame\Controller;

use THCFrame\Core\Base;
use THCFrame\Request\RequestMethods;
use THCFrame\Controller\Exception;
use THCFrame\Request\Response;
use THCFrame\Registry\Registry;
use THCFrame\Core\Lang;

/**
 * Base controller for REST Api
 *
 * @author Tomy
 */
class RestController extends Base
{

    /**
     * @readwrite
     */
    protected $defaultContentType = 'application/json';

    /**
     * @readwrite
     */
    protected $method;

    /**
     * @read
     * @var THCFrame\Request\Response
     */
    protected $response;

    /**
     * Store initialized cache object.
     *
     * @var THCFrame\Cache\Cache
     * @read
     */
    protected $cache;

    /**
     * Store configuration.
     *
     * @var THCFrame\Configuration\Configuration
     * @read
     */
    protected $config;

    /**
     * Store language extension.
     *
     * @var THCFrame\Core\Lang
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
     * @var THCFrame\Session\Driver
     */
    protected $session;

    /**
     *
     * @param type $data
     * @return type
     */
    private function _cleanInputs($data)
    {
        $cleanInput = [];

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $cleanInput[$k] = $this->_cleanInputs($v);
            }
        } else {
            $cleanInput = trim(htmlentities(strip_tags($data), ENT_QUOTES, 'UTF-8'));
        }
        return $cleanInput;
    }

    /**
     *
     * @param type $options
     * @throws Exception\Header
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        $this->response = new Response();
        $this->session = Registry::get('session');
        $this->serverHost = RequestMethods::server('HTTP_HOST');
        $this->cache = Registry::get('cache');
        $this->config = Registry::get('configuration');
        $this->lang = Lang::getInstance();

        $this->response->setHeader('Access-Control-Allow-Orgin', '*')
                ->setHeader('Access-Control-Allow-Methods', '*');
    }

    /**
     * Return server url with http schema
     *
     * @return type
     */
    public function getServerHost()
    {
        if ((!empty(RequestMethods::server('REQUEST_SCHEME')) && RequestMethods::server('REQUEST_SCHEME') == 'https')
                || (!empty(RequestMethods::server('HTTPS')) && RequestMethods::server('HTTPS') == 'on')
                || (!empty(RequestMethods::server('SERVER_PORT')) && RequestMethods::server('SERVER_PORT') == '443')) {
            $serverRequestScheme = 'https://';
        } else {
            $serverRequestScheme = 'http://';
        }
        return $serverRequestScheme . RequestMethods::server('HTTP_HOST');
    }

    /**
     *
     * @param type $message
     * @param type $status
     * @param type $error
     */
    protected function ajaxResponse($message, $error = false, $status = 200,
            array $additionalData = [])
    {
        $data = [
            'message' => $message,
            'error' => (bool) $error,
                ] + $additionalData;

        session_write_close();

        $this->response->setHttpVersionStatusHeader('HTTP/1.1 ' . (int) $status . ' ' . $this->response->getStatusMessageByCode($status))
                ->setHeader('Content-Type', $this->defaultContentType)
                ->setData($data);

        $this->response->sendHeaders();
        $this->response->send();
    }

    /**
     * Check if provided api token is valid or not
     *
     * @protected
     */
    public function checkUserApiToken()
    {
        $apiToken = \THCFrame\Security\Model\ApiTokenModel::first(['token = ?' => RequestMethods::post('apiToken')]);

        if (null !== $apiToken) {
            $user = \App\Model\UserModel::first(['id = ?' => (int) $apiToken->getUserId()]);

            if (null === $user) {
                $this->ajaxResponse('User not found', true, 404);
            }
        } else {
            $this->ajaxResponse('Api token is not valid', true, 401);
        }
    }

    /**
     *
     * @param \THCFrame\Security\Model\ApiTokenModel $apiToken
     * @param \THCFrame\Controller\THCFrame\Request\Response $response
     * @param type $method
     * @param type $request
     */
    public function logApiRequestResponseData(\THCFrame\Security\Model\ApiTokenModel $apiToken,
            THCFrame\Request\Response $response, $method, $request)
    {
        $apiLog = new \THCFrame\Security\Model\ApiRequestLogModel([
            'userId' => $apiToken->getUserId(),
            'apiId' => $apiToken->getId(),
            'requestMethod' => $method,
            'apiRequest' => serialize($request),
            'apiResponse' => serialize($response),
        ]);

        if ($apiLog->validate()) {
            $apiLog->save();
        } else {
            \THCFrame\Core\Core::getLogger()->error('ApiLog {apiId} - {method} - {request} - {response}', [
                'apiId' => $apiToken->getId(),
                'method' => $method,
                'request' => json_encode($request),
                'response' => json_encode($response)
                    ]
            );
        }
    }

    public function getDefaultContentType()
    {
        return $this->defaultContentType;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getResponse()
    {
        return $this->response;
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

    public function setDefaultContentType($defaultContentType)
    {
        $this->defaultContentType = $defaultContentType;
        return $this;
    }

    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    public function setResponse(THCFrame\Request\Response $response)
    {
        $this->response = $response;
        return $this;
    }

    public function setCache(THCFrame\Cache\Cache $cache)
    {
        $this->cache = $cache;
        return $this;
    }

    public function setConfig(THCFrame\Configuration\Configuration $config)
    {
        $this->config = $config;
        return $this;
    }

    public function setLang(THCFrame\Core\Lang $lang)
    {
        $this->lang = $lang;
        return $this;
    }

    public function setSession(THCFrame\Session\Driver $session)
    {
        $this->session = $session;
        return $this;
    }

}
