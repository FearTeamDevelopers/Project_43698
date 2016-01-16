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

    private function _response($data, $status = 200)
    {
        $this->_response->setHttpVersionStatusHeader(
                "HTTP/1.1 " . $status . " " . $this->_response->getStatusMessageByCode($status)
                );
        $this->_response->setBody($data);
        return $this->_response;
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
        $this->_response->setHeader('Access-Control-Allow-Orgin', '*')
                ->setHeader('Access-Control-Allow-Methods', '*');

        $this->method = RequestMethods::server('REQUEST_METHOD');
        if ($this->method == 'POST' && RequestMethods::issetserver('HTTP_X_HTTP_METHOD')) {
            if (RequestMethods::server('HTTP_X_HTTP_METHOD') == 'DELETE') {
                $this->method = 'DELETE';
            } else if (RequestMethods::server('HTTP_X_HTTP_METHOD') == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception\Header("Unexpected Header");
            }
        }
    }

    /**
     * 
     * @param string $resource
     * @param array $args
     * @param string $type      Resource|Collection
     * @return type
     */
    public function runApi($resource, $args = array(), $type = 'Resource')
    {
        $this->checkAuthToken();
        
        $route = \THCFrame\Registry\Registry::get('router')->getLastPath();
        $parameters = $route->getMapArguments();
        
        if(empty($parameters)){
            $type = 'Collection';
        }
        
        switch ($this->method) {
            case 'DELETE':
                $actionName = strtolower($resource) . 'ResourceDelete';
                break;
            case 'POST':
                $actionName = strtolower($resource) . ucfirst($type) . 'Update';
                break;
            case 'GET':
                $actionName = strtolower($resource) . ucfirst($type) . 'Retriew';
                break;
            case 'PUT':
                $actionName = strtolower($resource) . ucfirst($type) . 'Create';
                break;
            default:
                $this->_response('Invalid request method', 405);
                break;
        }

        if (method_exists($this, $actionName)) {
            return $this->_response($this->{$actionName}($args));
        }

        return $this->_response(sprintf('Method %s not implemented', $actionName), 404);
    }

    public function checkAuthToken()
    {
        
    }
    
    public function generateAuthToken()
    {
        
    }
}
