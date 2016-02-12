<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;
use Gdbots\Pbjc\Validator\ConstraintValidator;
use Gdbots\Pbjc\Validator\Exception\UnexpectedTypeException;

/**
 * Validates values are equal (==).
 */
class IsMixinValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof IsMixin) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\IsMixin');
        }

        $comparedValue = $constraint->value;

        if ($value !== $comparedValue) {
            return str_replace(
                [
                    '{{ value }}',
                    '{{ compared_value }}',
                ],
                [
                    $value,
                    $comparedValue,
                ],
                $constraint->message
            );
        }
    }
}
