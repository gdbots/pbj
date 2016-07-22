<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldValidEnumValue implements Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b /* ignored */)
    {
        $fields = array_merge($a->getInheritedFields(), $a->getFields());

        /** @var \Gdbots\Pbjc\FieldDescriptor $field */
        foreach ($fields as $name => $field) {
            if ($field->getEnum()
                && $field->getDefault()
                && !$field->getEnum()->hasValue($field->getDefault())
            ) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" enum value "%s" doesn\'t exists. Check enum "%s" for all existing values.',
                    $a->toString(),
                    $field->getDefault(),
                    $name,
                    $field->getEnum()->toString()
                ));
            }
        }
    }
}
