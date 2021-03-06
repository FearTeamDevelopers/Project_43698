<?php

namespace THCFrame\Request;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Core\StringMethods;
use THCFrame\Request\RequestMethods;
use THCFrame\Request\Exception;

/**
 * Class represents the different types of request methods, but ultimately they all call the
 * same request() method. Other things to note are that the constructor sets the user agent, and the
 * get method turns a provided parameter array into a valid querystring.
 */
class Request extends Base
{

    protected $_request;

    /**
     * @readwrite
     */
    public $_willFollow = false;

    /**
     * @readwrite
     */
    protected $_willShareSession = true;

    /**
     * @readwrite
     */
    protected $_headers = [];

    /**
     * @readwrite
     */
    protected $_options = [];

    /**
     * @readwrite
     */
    protected $_referer;

    /**
     * @readwrite
     */
    protected $_agent;

    /**
     *
     * @param string $method
     * @return \THCFrame\Request\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    protected function _setOption($key, $value)
    {
        curl_setopt($this->_request, $key, $value);
        return $this;
    }

    /**
     * @param $key
     * @return string
     */
    protected function _normalize($key)
    {
        return 'CURLOPT_' . str_replace('CURLOPT_', '', strtoupper($key));
    }

    /**
     * Method sets Curl parameters relating to each of the different request methods.
     * Some request methods need additional parameters set (such as GET and POST),
     * while others need things excluded from the response (such as HEAD)
     *
     * @param $method
     * @return \THCFrame\Request\Request
     */
    protected function _setRequestMethod($method)
    {
        switch (strtoupper($method)) {
            case 'HEAD':
                $this->_setOption(CURLOPT_NOBODY, true);
                break;
            case 'GET':
                $this->_setOption(CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                $this->_setOption(CURLOPT_POST, true);
                break;
            default:
                $this->_setOption(CURLOPT_CUSTOMREQUEST, $method);
                break;
        }

        return $this;
    }

    /**
     * Method iterates through all the request-specific parameters that need to be set.
     * This includes the URL, the user agent, whether the request should follow redirects, and so on.
     * It even adds any options specified by the use of the setOptions() setter method (or construction option)
     *
     * @param $url
     * @param $parameters
     * @return \THCFrame\Request\Request
     */
    protected function _setRequestOptions($url, $parameters)
    {
        $this->_setOption(CURLOPT_URL, $url)
                ->_setOption(CURLOPT_HEADER, true)
                ->_setOption(CURLOPT_RETURNTRANSFER, true)
                ->_setOption(CURLOPT_USERAGENT, $this->_agent);

        if (!empty($parameters)) {
            $this->_setOption(CURLOPT_POSTFIELDS, $parameters);
        }

        if ($this->_willFollow) {
            $this->_setOption(CURLOPT_FOLLOWLOCATION, true);
        }

        if ($this->_willShareSession) {
            $this->_setOption(CURLOPT_COOKIE, session_name() . '=' . session_id());
        }

        if ($this->_referer) {
            $this->_setOption(CURLOPT_REFERER, $this->_referer);
        }

        foreach ($this->_options as $key => $value) {
            $this->_setOption(constant($this->_normalize($key)), $value);
        }

        return $this;
    }

    /**
     * Method iterates through the headers specified by the setHeaders()
     * setter method (or construction options) to add any custom headers to the request
     *
     * @return \THCFrame\Request\Request
     */
    protected function _setRequestHeaders()
    {
        $headers = [];

        foreach ($this->_headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        $this->_setOption(CURLOPT_HTTPHEADER, $headers);
        return $this;
    }

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);
        $this->_agent = RequestMethods::server('HTTP_USER_AGENT', 'Curl/PHP ' . PHP_VERSION);
    }

    /**
     * @param $url
     * @param array $parameters
     * @return Request\Response
     * @throws Exception\Response
     */
    public function delete($url, $parameters = [])
    {
        return $this->request('DELETE', $url, $parameters);
    }

    /**
     * @param $url
     * @param array $parameters
     * @return Request\Response
     * @throws Exception\Response
     */
    public function get($url, $parameters = [])
    {
        if (!empty($parameters)) {
            $url .= StringMethods::indexOf($url, '?') ? '&' : '?';
            $url .= is_string($parameters) ? $parameters : http_build_query($parameters, '', '&');
        }
        return $this->request('GET', $url);
    }

    /**
     * @param $url
     * @param array $parameters
     * @return Request\Response
     * @throws Exception\Response
     */
    public function head($url, $parameters = [])
    {
        return $this->request('HEAD', $url, $parameters);
    }

    /**
     * @param $url
     * @param array $parameters
     * @return Request\Response
     * @throws Exception\Response
     */
    public function post($url, $parameters = [])
    {
        return $this->request('POST', $url, $parameters);
    }

    /**
     * @param $url
     * @param array $parameters
     * @return Request\Response
     * @throws Exception\Response
     */
    public function put($url, $parameters = [])
    {
        return $this->request('PUT', $url, $parameters);
    }

    /**
     * The request() method use Curl to make the HTTP requests.
     * The method begins by creating a new curl resource instance and continues by setting some parameters of the
     * instance. It then makes the request, and if the request is successful, it will be returned in the form of a
     * Request\Response class instance. If the request fails, an exception will be raised.
     * Finally, the curl resource is destroyed and the response is returned.
     *
     * @param $method
     * @param $url
     * @param array $parameters
     * @return bool|string|Response
     * @throws Exception\Response
     */
    public function request($method, $url, $parameters = [])
    {
        session_write_close();

        Event::fire('framework.request.request.before', [$method, $url, $parameters]);

        $request = $this->_request = curl_init();

        if (is_array($parameters)) {
            $parameters = http_build_query($parameters, '', '&');
        }

        $this->_setRequestMethod($method)
                ->_setRequestOptions($url, $parameters)
                ->_setRequestHeaders();

        if(ENV === 'dev'){
            curl_setopt($request, CURLOPT_SSL_VERIFYPEER, false);
        }

        $response = curl_exec($request);

        if (!headers_sent()) {
            session_start();
        }

        if ($response) {
            $response = new Response([
                'response' => $response
            ]);
        } else {
            throw new Exception\Response(ucfirst(curl_error($request)));
        }

        Event::fire('framework.request.request.after', [$method, $url, $parameters, $response]);

        curl_close($request);
        return $response;
    }

}
