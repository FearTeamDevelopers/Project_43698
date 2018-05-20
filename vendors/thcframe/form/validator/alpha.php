<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;
use THCFrame\Core\StringMethods;

/**
 * Description of Alpha
 *
 * @author Tomy
 */
class Alpha extends AbstractValidator
{

    const ALPHA = 'alpha';

    protected $_messageTemplates = [
        self::ALPHA => "'%value%' does not contains alpha chars only"
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

        $result = StringMethods::match($value, "#([a-zá-žA-ZÁ-Ž{$pattern}]+)#");

        if (!$result) {
            $this->error(self::ALPHA);
            return false;
        }

        return true;
    }

}
