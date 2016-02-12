<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;
use Gdbots\Pbjc\Validator\ConstraintValidator;
use Gdbots\Pbjc\Validator\Exception\ConstraintDefinitionException;
use Gdbots\Pbjc\Validator\Exception\UnexpectedTypeException;
use Gdbots\Pbjc\EnumDescriptor;

/**
 * Validates enum type is equal (==) and all expected values exists.
 */
class EnumValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Enum) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Enum');
        }

        if (!$constraint->enum instanceof EnumDescriptor) {
            throw new ConstraintDefinitionException('The "enum" must be specified on constraint Enum');
        }

        if (!$value instanceof EnumDescriptor) {
            throw new UnexpectedTypeException($value, 'EnumDescriptor');
        }

        if ($value->getName() !== $constraint->enum->getName()) {
            return str_replace(
                [
                    '{{ name }}',
                    '{{ value }}',
                    '{{ compared_value }}',
                ],
                [
                    $constraint->enum->getName(),
                    $value->getName(),
                    $constraint->enum->getName(),
                ],
                $constraint->message
            );
        }

        if ($value->getType() !== $constraint->enum->getType()) {
            return str_replace(
                [
                    '{{ name }}',
                    '{{ value }}',
                    '{{ compared_value }}',
                ],
                [
                    $constraint->enum->getName(),
                    $value->getType(),
                    $constraint->enum->getType(),
                ],
                $constraint->messageType
            );
        }

        $diff = array_diff($constraint->enum->getValues(), $value->getValues());
        if (count($diff)) {
            return str_replace(
                [
                    '{{ name }}',
                    '{{ value }}',
                ],
                [
                    $value->getName(),
                    '"'.implode('", "', $diff).'"',
                ],
                $constraint->messageValues
            );
        }
    }
}
