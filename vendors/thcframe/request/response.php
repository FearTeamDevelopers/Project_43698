<?php

namespace THCFrame\Request;

use THCFrame\Core\Base;
use THCFrame\Request\Exception;

/**
 * Class accepts a response constructor option, which is the result of an HTTP request. 
 * It splits this response string into headers and a body, which are available through getter
 * methods
 */
class Response extends Base
{

    protected $_response;

    /**
     * @read
     */
    protected $_body = null;

    /**
     * @read
     */
    protected $_headers = array();

    /**
     * @readwrite
     */
    protected $_httpVersionStatusHeader;
    
    /**
     * 
     * @param type $method
     * @return \THCFrame\Request\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Object constructor
     * 
     * @param array $options
     */
    public function __construct($options = array())
    {
        if (!empty($options['response'])) {
            $response = $this->_response = $options['response'];
            unset($options['response']);
        }

        parent::__construct($options);

        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';
        preg_match_all($pattern, $response, $matches);

        $headers = array_pop($matches[0]);
        $headers = explode(PHP_EOL, str_replace('\r\n\r\n', '', trim($headers)));

        $this->_body = str_replace($headers, '', $response);

        $version = array_shift($headers);
        $this->setHttpVersionStatusHeader($version);

        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s?(.*)#', $header, $matches);
            $this->setHeader($matches[1], $matches[2]);
        }
    }

    /**
     * 
     * @return type
     */
    public function __toString()
    {
        return $this->_body;
    }

    /**
     * Set header
     * 
     * @param string $type
     * @param string $content
     * @return \THCFrame\Request\Response
     */
    public function setHeader($type, $content)
    {
        if (!isset($this->_headers[$type]) || $this->_headers[$type] != $content) {
            $this->_headers[$type] = $content;
        }

        return $this;
    }

    /**
     * Check if headers can be send
     * 
     * @return boolean
     */
    public function canSendHeaders()
    {
        if (headers_sent()) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Send headers
     */
    public function sendHeaders()
    {
        if ($this->canSendHeaders() && !empty($this->_headers)) {
            header($this->getHttpVersionStatusHeader());
            
            foreach ($this->_headers as $type => $content) {
                header("{$type}: {$content}");
            }
        }
    }

    /**
     * 
     * @param string $body
     */
    public function setBody($body)
    {
        $this->_body = $body;
    }

    /**
     * Prepend string to body
     * 
     * @param string $string
     */
    public function prependBody($string)
    {
        $this->_body = $string . $this->_body;
    }

    /**
     * Append string to body
     * 
     * @param string $string
     */
    public function appendBody($string)
    {
        $this->_body .= $string;
    }

}
