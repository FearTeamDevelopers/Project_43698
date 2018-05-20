<?php

namespace THCFrame\Form\Elements;

use THCFrame\Form\Validators\AbstractValidator;
use THCFrame\Core\Base;

/**
 * Description of abstractelement
 *
 * @author Tomy
 */
abstract class AbstractElement extends Base
{

    /**
     * @readwrite
     * @var string
     */
    protected $_id;

    /**
     * @readwrite
     * @var array
     */
    protected $_class = [];

    /**
     * @readwrite
     * @var string
     */
    protected $_name;

    /**
     * @readwrite
     * @var string
     */
    protected $_value;

    /**
     * @readwrite
     * @var string
     */
    protected $_title;

    /**
     * @readwrite
     * @var string
     */
    protected $_pattern;

    /**
     * @readwrite
     * @var string
     */
    protected $_placeholder;

    /**
     * @readwrite
     * @var THCFrame\Form\Elements\Label
     */
    protected $_label;

    /**
     * @read
     * @var array
     */
    protected $_validators = [];

    public function __construct($options = [])
    {
        parent::__construct($options);
    }

    public function addValidator(AbstractValidator $validator)
    {
        $this->_validators[] = $validator;
    }

    public function getValidationErrors()
    {
        $errors = [];

        if (!empty($this->_validators)) {
            foreach ($this->_validators as $validator) {
                $errors += $validator->getMessages();
            }
        }

        return $errors;
    }

    abstract public function render();

    // ------------------------ Getters and Setters ----------------------------
    public function getId()
    {
        return $this->_id;
    }

    public function getClass()
    {
        return $this->_class;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function getTitle()
    {
        return $this->_title;
    }

    public function getPattern()
    {
        return $this->_pattern;
    }

    public function getValidators()
    {
        return $this->_validators;
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    public function setClass($class)
    {
        $this->_class = $class;
        return $this;
    }

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function setValue($value)
    {
        $this->_value = $value;
        return $this;
    }

    public function setTitle($title)
    {
        $this->_title = $title;
        return $this;
    }

    public function setPattern($pattern)
    {
        $this->_pattern = $pattern;
        return $this;
    }

    public function getPlaceholder()
    {
        return $this->_placeholder;
    }

    public function setPlaceholder($placeholder)
    {
        $this->_placeholder = $placeholder;
        return $this;
    }

    public function getLabel()
    {
        return $this->_label;
    }

    public function setLabel(Label $label)
    {
        $this->_label = $label;
        return $this;
    }

}
