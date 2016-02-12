<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;
use Gdbots\Pbjc\Validator\ConstraintValidator;
use Gdbots\Pbjc\Validator\Exception\ConstraintDefinitionException;
use Gdbots\Pbjc\Validator\Exception\UnexpectedTypeException;
use Gdbots\Pbjc\FieldDescriptor;

/**
 * Validates enum type is equal (==) and all expected values exists.
 */
class FieldValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Field) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Field');
        }

        if (!$constraint->field instanceof FieldDescriptor) {
            throw new ConstraintDefinitionException('The "field" must be specified on constraint Field');
        }

        if (!$value instanceof FieldDescriptor) {
            throw new UnexpectedTypeException($value, 'FieldDescriptor');
        }

        $value = $value->toArray();

        foreach ($constraint->field->toArray() as $key => $comparedValue) {
            if (in_array($key , $constraint->ignore)) {
                continue;
            }

            $a = $value[$key];
            $b = $comparedValue;

            switch ($key) {
                case 'enum':
                    if ($a) $a = $a->getName();
                    if ($b) $b = $b->getName();
                    break;

                case 'any_of':
                    if ($a) $a = implode(', ', $a);
                    if ($b) $b = implode(', ', $b);
                    break;
            }

            if ($a !== $b) {
                return str_replace(
                    [
                        '{{ name }}',
                        '{{ value }}',
                        '{{ compared_value }}',
                    ],
                    [
                        $key,
                        $a,
                        $b,
                    ],
                    $constraint->message
                );
            }
        }
    }
}
