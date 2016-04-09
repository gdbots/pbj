<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaMustContainsField implements Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        $diff = array_diff(
            array_keys(array_merge($a->getFields(), $a->getInheritedFields())),
            array_keys(array_merge($b->getFields(), $b->getInheritedFields()))
        );
        if (count($diff)) {
            throw new ValidatorException(sprintf(
                'The schema "%s" must include the following field(s): "%s".',
                $b,
                implode('", "', $diff)
            ));
        }
    }
}
