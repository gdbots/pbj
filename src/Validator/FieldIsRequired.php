<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldIsRequired implements Constraint
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        $fa = array_merge($a->getInheritedFields(), $a->getFields());
        $fb = array_merge($b->getInheritedFields(), $b->getFields());

        /** @var \Gdbots\Pbjc\FieldDescriptor $field */
        /** @var \Gdbots\Pbjc\FieldDescriptor[] $fb */
        foreach ($fa as $name => $field) {
            if (!isset($fb[$name])) {
                continue;
            }

            if ($field->isRequired() && !$fb[$name]->isRequired()) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" must be required.',
                    $b,
                    $name
                ));
            }
        }
    }
}
