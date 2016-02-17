<?php

namespace Gdbots\Pbjc\Assert;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldSameEnum implements Assert
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

            if ($field->getEnum() != $fb[$name]->getEnum()
             && $field->getEnum()->getName() != $fb[$name]->getEnum()->getName()
            ) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" enum must be "%s".',
                    $b,
                    $name,
                    $field->getEnum()->getName()
                ));
            }
        }
    }
}
