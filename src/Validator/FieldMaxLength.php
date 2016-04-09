<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldMaxLength implements Constraint
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
            if (!isset($fb[$name]) || !$fb[$name]->getMaxLength()) {
                continue;
            }

            if (($field->getMaxLength() && $field->getMaxLength() > $fb[$name]->getMaxLength())
                || (!$field->getMaxLength() && $field->getType()->getMax() > $fb[$name]->getMaxLength())
            ) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" max length "%d" must be greater than or equal to "%d".',
                    $b,
                    $name,
                    $fb[$name]->getMaxLength(),
                    $field->getMaxLength() ?: $field->getType()->getMax()
                ));
            }
        }
    }
}
