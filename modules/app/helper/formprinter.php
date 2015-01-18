<?php

namespace App\Helper;

class FormPrinter
{

    /**
     * 
     * @param type $object
     * @param type $atribute
     * @return type
     */
    public static function iset($object, $atribute, $default = '')
    {
        return isset($object) ? htmlentities($object->$atribute) : $default;
    }

    /**
     * 
     * @param type $name
     * @param type $value
     * @param type $class
     * @param type $required
     * @return type
     */
    public static function textInput($name, $value = array(), $required = false, $class = 'longinput', $placeholder = '')
    {
        $htmlTag = '<input type="text" name="' . $name. '"';
        $htmlTagEnd = '/>';

        if (is_array($value) && !empty($value)) {
            $defaultValue = self::iset($value[0], $value[1], $value[2]);
            $htmlTag .= ' value="' . $defaultValue . '" ';
        }

        if ($placeholder !== '') {
            $htmlTag .= ' placeholder="'.$placeholder.'" ';
        }
        
        if ($class !== '') {
            $htmlTag .= ' class="' . $class . '" ';
        }

        if ($required) {
            $htmlTag .= " required ";
        }

        return mb_ereg_replace('\s+', ' ', $htmlTag . $htmlTagEnd);
    }
    
    /**
     * 
     * @param type $name
     * @param type $value
     * @param type $required
     * @param type $class
     * @param type $placeholder
     * @return type
     */
    public static function timeInput($name, $value = array(), $required = false, $class = 'longinput', $placeholder = '')
    {
        $htmlTag = '<input type="time" name="' . $name . '"';
        $htmlTagEnd = '/>';

        if (is_array($value) && !empty($value)) {
            $defaultValue = self::iset($value[0], $value[1], $value[2]);
            $htmlTag .= ' value="' . $defaultValue . '" ';
        }

        if ($placeholder !== '') {
            $htmlTag .= ' placeholder="'.$placeholder.'" ';
        }
        
        if ($class !== '') {
            $htmlTag .= ' class="' . $class . '" ';
        }

        if ($required) {
            $htmlTag .= " required ";
        }

        return mb_ereg_replace('\s+', ' ', $htmlTag . $htmlTagEnd);
    }

}
