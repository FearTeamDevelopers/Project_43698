<?php

namespace THCFrame\Form;

use THCFrame\Form\Elements\AbstractElement;
use THCFrame\Core\Base;

/**
 * Description of form
 *
 * @author Tomy
 */
class Form extends Base
{

    protected $elements = [];

    /**
     * @readwrite
     * @var string
     */
    protected $_action;

    /**
     * @readwrite
     * @var string
     */
    protected $_method;

    /**
     * @readwrite
     * @var string
     */
    protected $_enctype;

    /**
     * @readwrite
     * @var array
     */
    protected $_class;

    /**
     * @readwrite
     * @var string
     */
    protected $_id;

    public function __construct($options = [])
    {
        parent::__construct($options);
    }

    /**
     *
     * @param AbstractElement $element
     */
    public function addElement(AbstractElement $element)
    {
        $this->elements[$element->getName()] = $element;
    }

    /**
     *
     * @param array $elements
     */
    public function addElements(array $elements = [])
    {
        if (!empty($elements)) {
            foreach ($elements as $element) {
                if ($element instanceof AbstractElement) {
                    $this->addElement($element);
                }
            }
        }
    }

    /**
     *
     */
    public function validate()
    {
        if (!empty($this->elements)) {
            foreach ($this->elements as $element) {
                $validators = $element->getValidators();

                if (!empty($validators)) {
                    foreach ($validators as $validator) {
                        $validator->isValid($element->getValue());
                    }
                }
            }
        }
    }

    public function addProtection()
    {

    }

    public function render($elementsOnly = false)
    {

    }

    public function renderHeader()
    {

    }

    public function renderFooter()
    {
        return '</form>';
    }

    // ------------------------ Getters and Setters ----------------------------
    public function getElements()
    {
        return $this->elements;
    }

    public function getAction()
    {
        return $this->_action;
    }

    public function getMethod()
    {
        return $this->_method;
    }

    public function getEnctype()
    {
        return $this->_enctype;
    }

    public function getClass()
    {
        return $this->_class;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function setAction($action)
    {
        $this->_action = $action;
        return $this;
    }

    public function setMethod($method)
    {
        $this->_method = $method;
        return $this;
    }

    public function setEnctype($enctype)
    {
        $this->_enctype = $enctype;
        return $this;
    }

    public function setClass($class)
    {
        $this->_class = $class;
        return $this;
    }

    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

}
