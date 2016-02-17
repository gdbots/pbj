<?php

namespace Gdbots\Pbjc\Assert;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldGreaterOrEqualThan implements Assert
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

            if ($field->getMax() > $fb[$name]->getMax()) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" max value must be greater than or equal to "%d".',
                    $b,
                    $name,
                    $field->getMax()
                ));
            }
        }
    }
}
