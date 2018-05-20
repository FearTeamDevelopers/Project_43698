<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of Url
 *
 * @author Tomy
 */
class Url extends AbstractValidator
{

    const URL = 'url';

    protected $_messageTemplates = [
        self::URL => "'%value%' is not valid url"
    ];

    public function isValid($value)
    {
        $this->setValue($value);

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            $this->error(self::URL);
            return false;
        }

        return true;
    }

}
