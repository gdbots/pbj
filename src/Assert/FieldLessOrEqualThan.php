<?php

namespace Gdbots\Pbjc\Assert;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldLessOrEqualThan implements Assert
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        $fa = array_merge($a->getInheritedFields(), $a->getFields());
        $fb = array_merge($b->getInheritedFields(), $b->getFields());

        foreach ($fa as $name => $field) {
            if (!isset($fb[$name])) {
                continue;
            }

            if ($field->getMin() < $fb[$name]->getMin()) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" min value must be less than or equal to "%d".',
                    $b,
                    $name,
                    $field->getMin()
                ));
            }
        }
    }
}
