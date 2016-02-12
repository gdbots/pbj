<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;
use Gdbots\Pbjc\Validator\ConstraintValidator;
use Gdbots\Pbjc\Validator\Exception\ConstraintDefinitionException;
use Gdbots\Pbjc\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the value is one of the expected values.
 */
class ExtendChoiceValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof ExtendChoice) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\ExtendChoice');
        }

        if (!is_array($constraint->choices)) {
            throw new ConstraintDefinitionException('The "choices" must be specified on constraint Choice');
        }

        if (!is_array($value)) {
            throw new UnexpectedTypeException($value, 'array');
        }

        $diff = array_diff($constraint->choices, $value);
        if (count($diff)) {
            return str_replace(
                [
                    '{{ value }}',
                ],
                [
                    '"'.implode('", "', $diff).'"',
                ],
                $constraint->message
            );
        }
    }
}
