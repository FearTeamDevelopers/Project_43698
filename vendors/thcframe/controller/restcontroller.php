<?php

namespace THCFrame\Controller;

use THCFrame\Core\Base;
use THCFrame\Request\RequestMethods;
use THCFrame\Controller\Exception;
use THCFrame\Request\Response;

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
    protected $_defaultContentType = 'application/json';

    /**
     * @readwrite
     */
    protected $_method;

    /**
     * @read
     * @var THCFrame\Request\Response
     */
    protected $_response;

    /**
     * Store initialized cache object.
     *
     * @var THCFrame\Cache\Cache
     * @read
     */
    protected $_cache;

    /**
     * Store configuration.
     *
     * @var THCFrame\Configuration\Configuration
     * @read
     */
    protected $_config;

    /**
     * Store language extension.
     *
     * @var THCFrame\Core\Lang
     * @read
     */
    protected $_lang;

    /**
     * Store server host name.
     *
     * @var string
     * @read
     */
    protected $_serverHost;

    /**
     * Session object
     *
     * @read
     * @var THCFrame\Session\Driver
     */
    protected $_session;

    /**
     *
     * @param type $data
     * @return type
     */
    private function _cleanInputs($data)
    {
        $cleanInput = array();

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $cleanInput[$k] = $this->_cleanInputs($v);
            }
        } else {
            $cleanInput = trim(strip_tags($data));
        }
        return $cleanInput;
    }

    /**
     *
     * @param type $options
     * @throws Exception\Header
     */
    public function __construct($options = array())
    {
        parent::__construct($options);

        $this->_response = new Response();
        $this->_session = Registry::get('session');
        $this->_serverHost = RequestMethods::server('HTTP_HOST');
        $this->_cache = Registry::get('cache');
        $this->_config = Registry::get('configuration');
        $this->_lang = Lang::getInstance();

        $this->_response->setHeader('Access-Control-Allow-Orgin', '*')
                ->setHeader('Access-Control-Allow-Methods', '*');

        $this->_method = RequestMethods::server('REQUEST_METHOD');
        if ($this->_method == 'POST' && RequestMethods::issetserver('HTTP_X_HTTP_METHOD')) {
            if (RequestMethods::server('HTTP_X_HTTP_METHOD') == 'DELETE') {
                $this->_method = 'DELETE';
            } else if (RequestMethods::server('HTTP_X_HTTP_METHOD') == 'PUT') {
                $this->_method = 'PUT';
            } else {
                throw new Exception\Header("Unexpected Header");
            }
        }
    }

    /**
     *
     * @param type $message
     * @param type $status
     * @param type $error
     */
    protected function ajaxResponse($message, $error = false, $status = 200,
            array $additionalData = array())
    {
        $data = array(
            'message' => $message,
            'error' => (bool) $error,
                ) + $additionalData;

        $this->_response->setHttpVersionStatusHeader('HTTP/1.1 ' . (int) $status . ' ' . $this->_response->getStatusMessageByCode($status))
                ->setHeader('Content-type', $this->_defaultContentType)
                ->setData($data);

        $this->_response->sendHeaders();
        $this->_response->send();
    }

    /**
     * Check if provided api token is valid or not
     *
     * @protected
     */
    public function checkUserApiToken()
    {
        $apiToken = \THCFrame\Security\Model\ApiTokenModel::first(array('token = ?' => RequestMethods::post('apiV1Token')));

        if (null !== $apiToken) {
            $user = \App\Model\UserModel::first(array('id = ?' => (int) $apiToken->getUserId()));

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
        $apiLog = new \THCFrame\Security\Model\ApiRequestLogModel(array(
            'userId' => $apiToken->getUserId(),
            'apiId' => $apiToken->getId(),
            'requestMethod' => $method,
            'apiRequest' => serialize($request),
            'apiResponse' => serialize($response),
        ));

        if ($apiLog->validate()) {
            $apiLog->save();
        } else {
            \THCFrame\Core\Core::getLogger()->error('ApiLog {apiId} - {method} - {request} - {response}', array(
                'apiId' => $apiToken->getId(),
                'method' => $method,
                'request' => json_encode($request),
                'response' => json_encode($response)
                    )
            );
        }
    }

}
