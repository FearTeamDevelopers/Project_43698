<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of float
 *
 * @author Tomy
 */
class Float extends AbstractValidator
{

    const FLOAT = 'float';

    protected $_messageTemplates = [
        self::FLOAT => "'%value%' is not a floating point value"
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        if (!is_float($value)) {
            $this->error(self::FLOAT);
            return false;
        }

        return true;
    }

}
