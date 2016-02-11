<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;
use Gdbots\Pbjc\Validator\ConstraintValidator;
use Gdbots\Pbjc\Validator\Exception\UnexpectedTypeException;

/**
 * Provides a base class for the validation of property comparisons.
 */
abstract class AbstractComparisonValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof AbstractComparison) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\AbstractComparison');
        }

        if (null === $value) {
            return;
        }

        $comparedValue = $constraint->value;

        // Convert strings to DateTimes if comparing another DateTime
        // This allows to compare with any date/time value supported by
        // the DateTime constructor:
        // http://php.net/manual/en/datetime.formats.php
        if (is_string($comparedValue)) {
            if ($value instanceof \DatetimeImmutable) {
                // If $value is immutable, convert the compared value to a
                // DateTimeImmutable too
                $comparedValue = new \DatetimeImmutable($comparedValue);
            } elseif ($value instanceof \DateTime || $value instanceof \DateTimeInterface) {
                // Otherwise use DateTime
                $comparedValue = new \DateTime($comparedValue);
            }
        }

        if (!$this->compareValues($value, $comparedValue)) {
            return str_replace(
                [
                    '{{ value }}',
                    '{{ compared_value }}',
                    '{{ compared_value_type }}',
                ],
                [
                    $this->formatValue($value, self::OBJECT_TO_STRING | self::PRETTY_DATE),
                    $this->formatValue($comparedValue, self::OBJECT_TO_STRING | self::PRETTY_DATE),
                    $this->formatTypeOf($comparedValue),
                ],
                $constraint->message
            );
        }
    }

    /**
     * Compares the two given values to find if their relationship is valid.
     *
     * @param mixed $value1 The first value to compare
     * @param mixed $value2 The second value to compare
     *
     * @return bool true if the relationship is valid, false otherwise
     */
    abstract protected function compareValues($value1, $value2);
}
