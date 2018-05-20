<?php

namespace THCFrame\Form\Validator;

use THCFrame\Core\Base;

/**
 * Description of abstract
 *
 * @author Tomy
 */
abstract class AbstractValidator extends Base
{

    /**
     * @readwrite
     * @var mixed
     */
    protected $_value;

    /**
     * Array of error codes
     *
     * @read
     * @var array
     */
    protected $_errors = [];

    /**
     * Array of error messages
     *
     * @read
     * @var array
     */
    protected $_messages = [];

    /**
     * @read
     * @var array
     */
    protected $_translator;

    /**
     * @read
     * @var array
     */
    protected $_messageTemplates = [];

    /**
     * @read
     * @var array
     */
    protected $_messageVariables = [];

    public function __construct($options = [])
    {
        parent::__construct($options);
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function getMessages()
    {
        return $this->_messages;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setValue($value)
    {
        $this->_value = $value;
        return $this;
    }

    /**
     *
     *
     * @param type $messageKey
     * @param type $value
     */
    protected function error($messageKey = null, $value = null)
    {
        if ($value !== null) {
            $this->setValue($value);
        }

        if ($messageKey !== null && !in_array($messageKey, $this->_errors)) {
            $template = $this->_messageTemplates[$messageKey];
            $this->_errors[] = $messageKey;
            $message = str_replace('%value%', $this->getValue(), $template);

            if (!empty($this->_messageVariables)) {
                foreach ($this->_messageVariables as $varInTemplate => $variable) {
                    $get = 'get' . ucfirst($variable);
                    $message = str_replace('%' . $varInTemplate . '%', $this->$get(), $template);
                }
            }

            $this->_messages[] = $message;
        }
    }

    abstract public function isValid();
}
