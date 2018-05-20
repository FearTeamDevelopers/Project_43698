<?php

namespace THCFrame\Form\Elements;

use THCFrame\Form\Elements\AbstractElement;

/**
 * Description of select
 *
 * @author Tomy
 */
class Select extends AbstractElement
{

    const ELEMENT_NAME = 'button';
    const EMPTY_OPTION_VALUE = null;
    const EMPTY_OPTION_TEXT = '-- vyberte --';

    /**
     * @readwrite
     * @var bool
     */
    protected $_multiple = false;

    /**
     * @readwrite
     * @var int
     */
    protected $_size;

    /**
     * @readwrite
     * @var bool
     */
    protected $_addEmptyOption = true;

    /**
     * @readwrite
     * @var array
     */
    protected $_options = [];

    public function getOptions()
    {
        return $this->_options;
    }

    public function setOptions($options)
    {
        $this->_options = $options;
        return $this;
    }

    public function getMultiple()
    {
        return $this->_multiple;
    }

    public function getSize()
    {
        return $this->_size;
    }

    public function getAddEmptyOption()
    {
        return $this->_addEmptyOption;
    }

    public function setMultiple($multiple)
    {
        $this->_multiple = (bool) $multiple;
        return $this;
    }

    public function setSize($size)
    {
        $this->_size = $size;
        return $this;
    }

    public function setAddEmptyOption($addEmptyOption)
    {
        $this->_addEmptyOption = (bool) $addEmptyOption;
        return $this;
    }

    public function render()
    {
        $element = '<' . self::ELEMENT_NAME;
        $element .=!empty($this->_id) ? ' id="' . $this->_id . '"' : '';
        $element .=!empty($this->_name) ? ' name="' . $this->_name . '"' : '';
        $element .=!empty($this->_class) ? ' class="' . implode(' ', $this->_class) . '"' : '';
        $element .=!empty($this->_title) ? ' title="' . $this->_title . '"' : '';

        $element .=!empty($this->_multiple) ? ' multiple' : '';
        $element .=!empty($this->_size) ? ' size="' . $this->_size . '"' : '';

        $element = preg_replace('/\s+/', ' ', $element);

        $element .='>';
        $element .= $this->getOptionsForRender();
        $element .= '</' . self::ELEMENT_NAME . '>';

        return $element;
    }

    private function getOptionsForRender()
    {
        if (empty($this->_options)) {
            return '<option value="' . self::EMPTY_OPTION_VALUE . '">' . self::EMPTY_OPTION_TEXT . '</option>';
        } else {
            $options = [];

            if ($this->_addEmptyOption) {
                $options[] = '<option value="' . self::EMPTY_OPTION_VALUE . '">' . self::EMPTY_OPTION_TEXT . '</option>';
            }

            foreach ($this->_options as $key => $value) {
                if (is_array($value) && !empty($value)) {
                    $options[] = '<optgroup label="' . htmlentities($key) . '">';

                    foreach ($value as $subKey => $subVal) {
                        $selected = '';

                        if (!empty($this->_value)) {
                            if ((is_array($this->_value) && in_array($subKey, $this->_value)) || (!is_array($this->_value) && $subKey == $this->_value)) {
                                $selected = ' selected="selected"';
                            }
                        }

                        $options[] = '<option value="' . htmlentities($subKey) . '"' . $selected . '>' . htmlentities($subVal) . '</option>';
                    }

                    $options[] = '</optgroup>';
                    return implode('', $options);
                } elseif (!is_array($value)) {
                    $selected = '';

                    if (!empty($this->_value)) {
                        if ((is_array($this->_value) && in_array($key, $this->_value)) || (!is_array($this->_value) && $key == $this->_value)) {
                            $selected = ' selected="selected"';
                        }
                    }

                    $options[] = '<option value="' . htmlentities($key) . '"' . $selected . '>' . htmlentities($value) . '</option>';
                    return implode('', $options);
                } else {
                    return implode('', $options);
                }
            }
        }
    }

}
