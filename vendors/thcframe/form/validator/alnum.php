<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;
use THCFrame\Core\StringMethods;

/**
 * Description of Alnum
 *
 * @author Tomy
 */
class Alnum extends AbstractValidator
{

    const ALNUM = 'alnum';

    protected $_messageTemplates = [
        self::ALNUM => "'%value%' does not contains alphanumeric chars only"
    ];

    /**
     * @readwrite
     * @var bool
     */
    protected $_strict = false;

    public function isValid($value)
    {
        $this->setValue($value);
        $pattern = '';

        if ($this->_strict === false) {
            $pattern = preg_quote('#$%^&*()+=-[]\',./|\":?~_', '#');
        }

        $result = StringMethods::match($value, "#([a-zá-žA-ZÁ-Ž0-9{$pattern}]+)#");

        if (!$result) {
            $this->error(self::ALNUM);
            return false;
        }

        return true;
    }

}
