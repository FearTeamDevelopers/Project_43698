<?php

namespace THCFrame\Form\Elements;

use THCFrame\Form\Elements\AbstractElement;

/**
 * Description of text
 *
 * @author Tomy
 */
class Hidden extends AbstractElement
{

    const ELEMENT_NAME = 'input';
    const ELEMENT_TYPE = 'hidden';

    public function render()
    {
        $element = '<' . self::ELEMENT_NAME;
        $element .= ' type="' . self::ELEMENT_TYPE . '"';
        $element .=!empty($this->_id) ? ' id="' . $this->_id . '"' : '';
        $element .=!empty($this->_name) ? ' name="' . $this->_name . '"' : '';

        $element = preg_replace('/\s+/', ' ', $element);

        $element .=!empty($this->_value) ? ' value="' . htmlentities($this->_value) . '"' : '';
        $element .= ' />';

        return $element;
    }

}
