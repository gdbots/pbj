<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\Validator\ConstraintInterface;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemeContainsEnum implements ConstraintInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        $diff = array_diff(
            array_keys($a->getEnums()),
            array_keys($b->getEnums())
        );
        if (count($diff)) {
            throw new ValidatorException(sprintf(
                'The schema "%s" must include the following enum(s): "%s".',
                $b,
                implode('", "', $diff)
            ));
        }
    }
}
