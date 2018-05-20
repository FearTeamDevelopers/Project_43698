<?php

namespace THCFrame\Form\Elements;

use THCFrame\Form\Elements\AbstractElement;

/**
 * Description of text
 *
 * @author Tomy
 */
class Text extends AbstractElement
{

    const ELEMENT_NAME = 'input';
    const ELEMENT_TYPE = 'text';

    public function render()
    {
        $element = '<' . self::ELEMENT_NAME;
        $element .= ' type="' . self::ELEMENT_TYPE . '"';
        $element .=!empty($this->_id) ? ' id="' . $this->_id . '"' : '';
        $element .=!empty($this->_name) ? ' name="' . $this->_name . '"' : '';
        $element .=!empty($this->_class) ? ' class="' . implode(' ', $this->_class) . '"' : '';
        $element .=!empty($this->_pattern) ? ' pattern="' . $this->_pattern . '"' : '';
        $element .=!empty($this->_title) ? ' title="' . $this->_title . '"' : '';
        $element .=!empty($this->_placeholder) ? ' placeholder="' . $this->_placeholder . '"' : '';

        $element = preg_replace('/\s+/', ' ', $element);

        $element .=!empty($this->_value) ? ' value="' . htmlentities($this->_value) . '"' : '';
        $element .= ' />';

        return $element;
    }

}
