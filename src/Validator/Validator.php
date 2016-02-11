<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Validator\Exception\ValidatorException;

/**
 * Validates values against constraints.
 */
class Validator
{
    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Validates a value against a constraint or a list of constraints.
     *
     * @param mixed      $value       The value to validate
     * @param Constraint $constraints The constraint to validate against
     *
     * @return string|null An error message of constraint violation.
     *                     If returns null, validation succeeded
     *
     * @thorw ValidatorException If validator doesn't exists
     */
    public static function validate($value, Constraint $constraint)
    {
        $className = $constraint->validatedBy();

        if (!class_exists($className)) {
            throw new ValidatorException(sprintf(
                'Missing validator class "%s".',
                $className
            ));
        }

        $validator = new $className();

        return $validator->validate($value, $constraint);
    }
}
