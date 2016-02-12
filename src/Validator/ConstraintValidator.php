<?php

namespace Gdbots\Pbjc\Validator;

/**
 * Base class for constraint validators.
 */
abstract class ConstraintValidator
{
    /**
     * Checks if the passed value is valid.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
    }
}
