<?php

namespace THCFrame\Form\Elements;

use THCFrame\Form\Elements\AbstractElement;

/**
 * Description of textarea
 *
 * @author Tomy
 */
class Textarea extends AbstractElement
{

    const ELEMENT_NAME = 'textarea';

    /**
     * @readwrite
     * @var int
     */
    protected $_rows;

    /**
     * @readwrite
     * @var int
     */
    protected $_cols;

    public function getRows()
    {
        return $this->_rows;
    }

    public function getCols()
    {
        return $this->_cols;
    }

    public function setRows($rows)
    {
        $this->_rows = $rows;
        return $this;
    }

    public function setCols($cols)
    {
        $this->_cols = $cols;
        return $this;
    }

    public function render()
    {
        $element = '<' . self::ELEMENT_NAME;
        $element .=!empty($this->_id) ? ' id="' . $this->_id . '"' : '';
        $element .=!empty($this->_name) ? ' name="' . $this->_name . '"' : '';
        $element .=!empty($this->_class) ? ' class="' . implode(' ', $this->_class) . '"' : '';
        $element .=!empty($this->_cols) ? ' cols="' . $this->_cols . '"' : '';
        $element .=!empty($this->_rows) ? ' rows="' . $this->_rows . '"' : '';

        $element = preg_replace('/\s+/', ' ', $element);

        $element .=!empty($this->_value) ? '>' . htmlentities($this->_value) : '';
        $element .= '</' . self::ELEMENT_NAME . '>';

        return $element;
    }

}
