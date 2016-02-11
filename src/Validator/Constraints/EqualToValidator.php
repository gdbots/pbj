<?php

namespace Gdbots\Pbjc\Validator\Constraints;

/**
 * Validates values are equal (==).
 */
class EqualToValidator extends AbstractComparisonValidator
{
    /**
     * {@inheritdoc}
     */
    protected function compareValues($value1, $value2)
    {
        return $value1 == $value2;
    }
}
