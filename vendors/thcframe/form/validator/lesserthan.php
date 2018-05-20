<?php

namespace THCFrame\Form\Validator;

use THCFrame\Form\Validator\AbstractValidator;

/**
 * Description of LesserThan
 *
 * @author Tomy
 */
class LesserThan extends AbstractValidator
{

    const MSG_NUMERIC = 'msgNumeric';
    const MSG_MAXIMUM = 'msgMaximum';

    protected $_messageVariables = [
        'max' => 'max'
    ];
    protected $_messageTemplates = [
        self::MSG_NUMERIC => "'%value%' is not numeric",
        self::MSG_MAXIMUM => "'%value%' must be no more than '%max%'"
    ];

    /**
     * @readwrite
     * @var numeric
     */
    protected $_max;

    public function isValid($value)
    {
        $isValid = true;
        $this->setValue($value);

        if (!isset($this->_max)) {
            throw new \THCFrame\Form\Exception\Argument('Maximum variable of lesser than validator is not set');
        }

        if (!is_numeric($value)) {
            $this->error(self::MSG_NUMERIC);
            $isValid = FALSE;
        }

        if ($value > $this->_max) {
            $this->error(self::MSG_MAXIMUM);
            $isValid = FALSE;
        }

        return $isValid;
    }

}
