<?php

namespace THCFrame\Form\Elements;

use THCFrame\Form\Elements\AbstractElement;

/**
 * Description of file
 *
 * @author Tomy
 */
class File extends AbstractElement
{

    const ELEMENT_NAME = 'input';
    const ELEMENT_TYPE = 'file';
    const MAX_FILE_SIZE = 15000000;

    protected $_accept;

    public function getAccept()
    {
        return $this->_accept;
    }

    public function setAccept($accept)
    {
        $this->_accept = $accept;
        return $this;
    }

    public function render()
    {
        $element = '<input type="hidden" name="MAX_FILE_SIZE" value="' . self::MAX_FILE_SIZE . '"/>';
        $element .= '<' . self::ELEMENT_NAME;
        $element .= ' type="' . self::ELEMENT_TYPE . '"';
        $element .=!empty($this->_id) ? ' id="' . $this->_id . '"' : '';
        $element .=!empty($this->_name) ? ' name="' . $this->_name . '"' : '';
        $element .=!empty($this->_class) ? ' class="' . implode(' ', $this->_class) . '"' : '';
        $element .=!empty($this->_title) ? ' title="' . $this->_title . '"' : '';
        $element .=!empty($this->_accept) ? ' accept="' . $this->_accept . '"' : '';

        $element = preg_replace('/\s+/', ' ', $element);
        $element .= ' />';

        return $element;
    }

}
