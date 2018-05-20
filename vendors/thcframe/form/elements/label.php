<?php

namespace THCFrame\Form\Elements;

use THCFrame\Form\Elements\AbstractElement;

/**
 * Description of label
 *
 * @author Tomy
 */
class Label extends AbstractElement
{

    const ELEMENT_NAME = 'label';

    /**
     * @readwrite
     * @var string
     */
    protected $_for;

    public function getFor()
    {
        return $this->_for;
    }

    public function setFor($for)
    {
        $this->_for = $for;
        return $this;
    }

    public function render()
    {
        $element = '<' . self::ELEMENT_NAME;
        $element .=!empty($this->_id) ? ' id="' . $this->_id . '"' : '';
        $element .=!empty($this->_name) ? ' name="' . $this->_name . '"' : '';
        $element .=!empty($this->_class) ? ' class="' . implode(' ', $this->_class) . '"' : '';
        $element .=!empty($this->_for) ? ' for="' . implode(' ', $this->_for) . '"' : '';

        $element = preg_replace('/\s+/', ' ', $element);

        $element .=!empty($this->_value) ? '>' . htmlentities($this->_value) : '';
        $element .= '</' . self::ELEMENT_NAME . '>';

        return $element;
    }

}
