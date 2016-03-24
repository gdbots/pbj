<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldIsNotRequired implements Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        $fa = array_merge($a->getFields(), $a->getInheritedFields());
        $fb = array_merge($b->getFields(), $b->getInheritedFields());

        /** @var \Gdbots\Pbjc\FieldDescriptor $field */
        /** @var \Gdbots\Pbjc\FieldDescriptor[] $fb */
        foreach ($fa as $name => $field) {
            if (!isset($fb[$name])) {
                continue;
            }

            if (!$field->isRequired() && $fb[$name]->isRequired()) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" must not be required.',
                    $b,
                    $name
                ));
            }
        }
    }
}
