<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of Email
 *
 * @author Tomy
 */
class Email extends AbstractValidator
{

    const EMAIL = 'email';

    protected $_messageTemplates = [
        self::EMAIL => "'%value%' is not valid email"
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->error(self::EMAIL);
            return false;
        }

        return true;
    }

}
