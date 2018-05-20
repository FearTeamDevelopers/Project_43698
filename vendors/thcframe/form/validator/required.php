<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of required
 *
 * @author Tomy
 */
class Required extends AbstractValidator
{

    const REQUIRED = 'required';

    protected $_messageTemplates = [
        self::REQUIRED => "'%value%' is required",
    ];

    /**
     * @readwrite
     * @var bool
     */
    protected $_strict = false;

    public function isValid($value)
    {
        $this->setValue($value);

        if ($this->_strict === true) {
            if ($value === 0) {
                return true;
            } elseif (mb_strlen($value) > 1) {
                $this->error(self::REQUIRED);
                return false;
            } else {
                $this->error(self::REQUIRED);
                return false;
            }
        } else {
            if (!empty($value)) {
                return true;
            } else {
                $this->error(self::REQUIRED);
                return false;
            }
        }
    }

}
