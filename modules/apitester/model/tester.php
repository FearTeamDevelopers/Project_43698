<?php

namespace Apitester\Model;

use THCFrame\Core\Base;
use THCFrame\Request\Request;

/**
 * TesterModel
 *
 * @author Tomy
 */
class Tester extends Base
{

    /**
     * @readwrite
     * @var string
     */
    protected $_requestUrl;

    /**
     * @readwrite
     * @var string
     */
    protected $_requestMethod = 'GET';

    /**
     * @readwrite
     * @var string
     */
    protected $_requestData;

    /**
     * @readwrite
     * @var string
     */
    protected $_response;

    /**
     *
     * @throws \THCFrame\Core\Exception\Argument
     */
    public function makeCall()
    {
        if (empty($this->_requestUrl)) {
            throw new \THCFrame\Core\Exception\Argument('Request url is not set');
        }

        $request = new Request();
        $parameters = $this->_requestData !== null ? json_decode($this->_requestData, true) : [];

        if (!empty($parameters)) {
            $response = $request->request($this->_requestMethod, $this->_requestUrl, $parameters);
        } else {
            $response = $request->request($this->_requestMethod, $this->_requestUrl);
        }

        $this->_response = $response;
    }

    public function getRequestUrl()
    {
        return $this->_requestUrl;
    }

    public function getRequestMethod()
    {
        return $this->_requestMethod;
    }

    public function getRequestData()
    {
        return $this->_requestData;
    }

    public function getResponse()
    {
        return $this->_response;
    }

    public function setRequestUrl($requestUrl)
    {
        $this->_requestUrl = $requestUrl;
        return $this;
    }

    public function setRequestMethod($requestMethod)
    {
        $this->_requestMethod = $requestMethod;
        return $this;
    }

    public function setRequestData($requestData)
    {
        $this->_requestData = $requestData;
        return $this;
    }

    public function setResponse($response)
    {
        $this->_response = $response;
        return $this;
    }

}
