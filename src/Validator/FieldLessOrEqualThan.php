<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldLessOrEqualThan implements Constraint
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
            if (!isset($fb[$name]) || !$fb[$name]->getMin()) {
                continue;
            }

            if (($field->getMin() && $field->getMin() < $fb[$name]->getMin())
                || (!$field->getMin() && $field->getType()->getMin() < $fb[$name]->getMin())
            ) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" min value "%d" must be less than or equal to "%d".',
                    $b,
                    $name,
                    $fb[$name]->getMin(),
                    $field->getMin() ?: $field->getType()->getMin()
                ));
            }
        }
    }
}
