<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of GreaterThan
 *
 * @author Tomy
 */
class GreaterThan extends AbstractValidator
{

    const MSG_NUMERIC = 'msgNumeric';
    const MSG_MINIMUM = 'msgMinimum';

    protected $_messageVariables = [
        'min' => 'min'
    ];
    protected $_messageTemplates = [
        self::MSG_NUMERIC => "'%value%' is not numeric",
        self::MSG_MINIMUM => "'%value%' must be at least '%min%'",
    ];

    /**
     * @readwrite
     * @var numeric
     */
    protected $_min;

    public function isValid($value)
    {
        $isValid = true;
        $this->setValue($value);

        if (!isset($this->_min)) {
            throw new \THCFrame\Form\Exception\Argument('Minimum variable of greater than validator is not set');
        }

        if (!is_numeric($value)) {
            $this->error(self::MSG_NUMERIC);
            $isValid = FALSE;
        }

        if ($value < $this->_min) {
            $this->error(self::MSG_MINIMUM);
            $isValid = FALSE;
        }

        return $isValid;
    }

}
