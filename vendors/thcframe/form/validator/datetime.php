<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of float
 *
 * @author Tomy
 */
class Datetime extends AbstractValidator
{

    const DATETIME = 'datetime';

    protected $_messageTemplates = [
        self::DATETIME => "'%value%' is not a valid date/datetime"
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        if (!(bool) strtotime($value)) {
            $this->error(self::DATETIME);
            return false;
        }

        return true;
    }

}
