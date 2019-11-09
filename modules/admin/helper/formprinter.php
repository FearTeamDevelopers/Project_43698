<?php

namespace Admin\Helper;

class FormPrinter
{
    /**
     * @param $object
     * @param $atribute
     * @param string $default
     * @return string
     */
    public static function iset($object, $atribute, $default = '')
    {
        return isset($object) ? htmlentities($object->$atribute) : $default;
    }

    /**
     * @param string $name
     * @param array $value
     * @param bool $required
     *
     * @param string $class
     * @param string $placeholder
     * @return string
     */
    public static function textInput($name, $value = [], $required = false, $class = 'width80', $placeholder = ''): string
    {
        $htmlTag = '<input type="text" name="'.$name.'"';
        $htmlTagEnd = '/>';

        if (is_array($value) && !empty($value)) {
            $default = $value[2] ?? '';
            $defaultValue = self::iset($value[0], $value[1], $default);
            $htmlTag .= ' value="'.$defaultValue.'" ';
        }

        if ($placeholder !== '') {
            $htmlTag .= ' placeholder="'.$placeholder.'" ';
        }

        if ($class !== '') {
            $htmlTag .= ' class="'.$class.'" ';
        }

        if ($required) {
            $htmlTag .= ' required ';
        }

        return mb_ereg_replace('\s+', ' ', $htmlTag.$htmlTagEnd);
    }

    /**
     * @param string $name
     * @param array $value
     * @param bool $required
     * @param string $class
     * @param string $placeholder
     *
     * @return string
     */
    public static function timeInput($name, $value = [], $required = false, $class = 'width80', $placeholder = ''): string
    {
        $htmlTag = '<input type="time" name="'.$name.'"';
        $htmlTagEnd = '/>';

        if (is_array($value) && !empty($value)) {
            $default = $value[2] ?? '';
            $defaultValue = self::iset($value[0], $value[1], $default);
            $htmlTag .= ' value="'.$defaultValue.'" ';
        }

        if ($placeholder !== '') {
            $htmlTag .= ' placeholder="'.$placeholder.'" ';
        }

        if ($class !== '') {
            $htmlTag .= ' class="'.$class.'" ';
        }

        if ($required) {
            $htmlTag .= ' required ';
        }

        return mb_ereg_replace('\s+', ' ', $htmlTag.$htmlTagEnd);
    }

    /**
     * @param string $type
     * @param string $name
     * @param array $value
     * @param array $options
     *
     * @return string
     */
    public static function input($type, $name, $value = [], $options = []): string
    {
        $htmlTag = '<input type="%s" name="%s"';
        $htmlTagEnd = '/>';

        if (is_array($value) && !empty($value)) {
            $default = $value[2] ?? '';
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
