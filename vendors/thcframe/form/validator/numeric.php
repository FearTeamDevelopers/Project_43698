<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of Numeric
 *
 * @author Tomy
 */
class Numeric extends AbstractValidator
{

    const MSG_NUMERIC = 'msgNumeric';

    protected $_messageTemplates = [
        self::MSG_NUMERIC => "'%value%' is not numeric",
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        if (!is_numeric($value)) {
            $this->error(self::MSG_NUMERIC);
            return false;
        }

        return true;
    }

}
