<?php

namespace Gdbots\Pbjc\Assert;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldMaxLength implements Assert
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        $fa = array_merge($a->getInheritedFields(), $a->getFields());
        $fb = array_merge($b->getInheritedFields(), $b->getFields());

        foreach ($fa as $name => $field) {
            if (!isset($fb[$name])
                || !$field->getType() instanceof StringType
                || !$fb[$name]->getType() instanceof StringType
            ) {
                continue;
            }

            if ($field->getMaxLength() > $fb[$name]->getMaxLength()) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" max length must be greater than or equal to "%d".',
                    $b,
                    $name,
                    $field->getMaxLength()
                ));
            }
        }
    }
}
