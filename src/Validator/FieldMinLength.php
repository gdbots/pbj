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
            if (!isset($fb[$name]) || !$fb[$name]->getMinLength()) {
                continue;
            }

            if (($field->getMinLength() && $field->getMinLength() < $fb[$name]->getMinLength())
              || (!$field->getMinLength() && $field->getType()->getMin() < $fb[$name]->getMinLength())
            ) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" min length "%d" must be less than or equal to "%d".',
                    $b,
                    $name,
                    $fb[$name]->getMinLength(),
                    $field->getMinLength() ?: $field->getType()->getMin()
                ));
            }
        }
    }
}
