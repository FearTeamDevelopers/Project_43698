<?php

namespace App\Helper;

class FormPrinter
{
    /**
     * @param mixed $object
     * @param string $atribute
     *
     * @param string $default
     * @return string
     */
    public static function iset($object, $atribute, $default = '')
    {
        return isset($object) ? htmlentities($object->$atribute) : $default;
    }

    /**
     * @param string $type
     * @param string $name
     * @param array $value
     * @param array $options
     *
     * @return string
     */
    public static function input($type, $name, $value = [], $options = [])
    {
        $htmlTag = '<input type="%s" name="%s"';
        $htmlTagEnd = '/>';

        if (is_array($value) && !empty($value)) {
            $default = isset($value[2]) ? $value[2] : '';
            $defaultValue = self::iset($value[0], $value[1], $default);
            $htmlTag .= ' value="'.$defaultValue.'" ';
        }

        if (!isset($options['class'])) {
            $htmlTag .= ' class="width80" ';
        }

        foreach ($options as $key => $value) {
            if ($value === true) {
                $htmlTag .= ' ' . $key . ' ';
            } else {
                $htmlTag .= ' ' . $key . '="' . $value . '" ';
            }
        }

        return mb_ereg_replace('\s+', ' ', sprintf($htmlTag, $type, $name).$htmlTagEnd);
    }
}
