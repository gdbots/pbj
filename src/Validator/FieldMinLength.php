<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldMinLength implements Assert
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

            if ($field->getMinLength() < $fb[$name]->getMinLength()) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" min length must be less than or equal to "%d".',
                    $b,
                    $name,
                    $field->getMinLength()
                ));
            }
        }
    }
}
