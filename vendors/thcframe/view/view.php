<?php

namespace THCFrame\View;

use THCFrame\Core\Base;
use THCFrame\Events\Events as Event;
use THCFrame\Session\Session;
use THCFrame\Template;
use THCFrame\View\Exception as Exception;
use THCFrame\Request\RequestMethods;
use THCFrame\Registry\Registry;

/**
 * View class
 */
class View extends Base
{

    CONST TITLE = 'title';
    CONST META_CANONICAL = 'canonical';
    CONST META_TITLE = 'metatitle';
    CONST META_DESCRIPTION = 'metadescription';

    /**
     * View file
     *
     * @readwrite
     */
    protected $file;

    /**
     * Storage for view data
     *
     * @readwrite
     */
    protected $data;

    /**
     * Template instance
     *
     * @read
     */
    protected $template;

    /**
     * Session object
     *
     * @var \THCFrame\Session\Session
     */
    private $session;

    /**
     * View constructor.
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        Event::fire('framework.view.construct.before', [$this->file]);

        $this->session = Registry::get('session');

        $this->template = new Template\Template([
            'implementation' => new Template\Implementation\Extended()
        ]);

        $this->_checkMessage();

        Event::fire('framework.view.construct.after', [$this->file, $this->template]);
    }

    /**
     *
     * @param string $method
     * @return \THCFrame\View\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * Method check if there is any message set or not
     */
    private function _checkMessage()
    {
        if ($this->session->get('infoMessage') !== null) {
            $this->set('infoMessage', $this->session->get('infoMessage'));
            $this->session->remove('infoMessage');
        } else {
            $this->set('infoMessage', '');
        }

        if ($this->session->get('warningMessage') !== null) {
            $this->set('warningMessage', $this->session->get('warningMessage'));
            $this->session->remove('warningMessage');
        } else {
            $this->set('warningMessage', '');
        }

        if ($this->session->get('successMessage') !== null) {
            $this->set('successMessage', $this->session->get('successMessage'));
            $this->session->remove('successMessage');
        } else {
            $this->set('successMessage', '');
        }

        if ($this->session->get('errorMessage') !== null) {
            $this->set('errorMessage', $this->session->get('errorMessage'));
            $this->session->remove('errorMessage');
        } else {
            $this->set('errorMessage', '');
        }

        if ($this->session->get('longFlashMessage') !== null) {
            $this->set('longFlashMessage', $this->session->get('longFlashMessage'));
            $this->session->remove('longFlashMessage');
        } else {
            $this->set('longFlashMessage', '');
        }
    }

    /**
     * @return mixed|string
     * @throws Template\Exception\Implementation
     * @throws Template\Exception\Parser
     */
    public function render()
    {
        Event::fire('framework.view.render.before', [$this->file]);

        if (!file_exists($this->file)) {
            return '';
        }

        return $this->template
                        ->parse(file_get_contents($this->file))
                        ->process($this->data);
    }

    /**
     *
     * @return null
     */
    public function getHttpReferer()
    {
        if (RequestMethods::server('HTTP_REFERER') === false) {
            return null;
        } else {
            return RequestMethods::server('HTTP_REFERER');
        }
    }

    /**
     *
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = '')
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return $default;
    }

    /**
     *
     * @param string|int $key
     * @param mixed $value
     * @throws Exception\Data
     */
    protected function _set($key, $value)
    {
        if (!is_string($key) && !is_numeric($key)) {
            throw new Exception\Data('Key must be a string or a number');
        }

        $data = $this->data;

        if (!$data) {
            $data = [];
        }

        $data[$key] = $value;
        $this->data = $data;
    }

    /**
     * @param string$key
     * @param null|mixed $value
     * @return $this
     * @throws Exception\Data
     */
    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $_key => $value) {
                $this->_set($_key, $value);
            }
            return $this;
        }

        $this->_set($key, $value);
        return $this;
    }

    /**
     *
     * @param type $key
     * @return \THCFrame\View\View
     */
    public function erase($key)
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     *
     * @param string $msg
     * @return mixed|string
     */
    public function infoMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->session->set('infoMessage', $msg);
        } else {
            return $this->get('infoMessage');
        }
    }

    /**
     *
     * @param string $msg
     * @return mixed|string
     */
    public function warningMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->session->set('warningMessage', $msg);
        } else {
            return $this->get('warningMessage');
        }
    }

    /**
     *
     * @param string $msg
     * @return mixed|string
     */
    public function successMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->session->set('successMessage', $msg);
        } else {
            return $this->get('successMessage');
        }
    }

    /**
     *
     * @param string $msg
     * @return mixed|string
     */
    public function errorMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->session->set('errorMessage', $msg);
        } else {
            return $this->get('errorMessage');
        }
    }

    /**
     *
     * @param string $msg
     * @return mixed|string
     */
    public function longFlashMessage($msg = '')
    {
        if (!empty($msg)) {
            $this->session->set('longFlashMessage', $msg);
        } else {
            return $this->get('longFlashMessage');
        }
    }

    /**
     *
     * @param string $title
     * @return $this
     * @throws Exception\Data
     */
    public function setTitle($title)
    {
        $this->_set(self::TITLE, $title);
        $this->_set(self::META_TITLE, $title);
        return $this;
    }

    /**
     *
     * @param string $title
     * @param string $canonical
     * @return $this
     * @throws Exception\Data
     */
    public function setBasicMeta($title, $canonical)
    {
        $this->_set(self::TITLE, $title);
        $this->_set(self::META_TITLE, $title);
        $this->_set(self::META_CANONICAL, $canonical);
        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getSession()
    {
        return $this->session;
    }

    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function setTemplate($template)
    {
        $this->template = $template;
        return $this;
    }

    public function setSession(Session $session)
    {
        $this->session = $session;
        return $this;
    }

}
