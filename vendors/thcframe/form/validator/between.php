<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of between
 *
 * @author Tomy
 */
class Between extends AbstractValidator
{

    const MSG_NUMERIC = 'msgNumeric';
    const MSG_MINIMUM = 'msgMinimum';
    const MSG_MAXIMUM = 'msgMaximum';

    protected $_messageVariables = [
        'min' => 'min',
        'max' => 'max'
    ];
    protected $_messageTemplates = [
        self::MSG_NUMERIC => "'%value%' is not numeric",
        self::MSG_MINIMUM => "'%value%' must be at least '%min%'",
        self::MSG_MAXIMUM => "'%value%' must be no more than '%max%'"
    ];

    /**
     * @readwrite
     * @var numeric
     */
    protected $_min;

    /**
     * @readwrite
     * @var numeric
     */
    protected $_max;

    public function isValid($value)
    {
        $isValid = true;
        $this->setValue($value);

        if (!isset($this->_min) && !isset($this->_max)) {
            throw new \THCFrame\Form\Exception\Argument('Minimum or maximum variables of between validator are not set');
        }

        if (!is_numeric($value)) {
            $this->error(self::MSG_NUMERIC);
            $isValid = FALSE;
        }

        if ($value > $this->_max) {
            $this->error(self::MSG_MAXIMUM);
            $isValid = FALSE;
        }

        if ($value < $this->_min) {
            $this->error(self::MSG_MINIMUM);
            $isValid = FALSE;
        }

        return $isValid;
    }

}
