<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldGreaterOrEqualThan implements Constraint
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
            if (!isset($fb[$name]) || !$fb[$name]->getMax()) {
                continue;
            }

            if (($field->getMax() && $field->getMax() > $fb[$name]->getMax())
                || (!$field->getMax() && $field->getType()->getMax() > $fb[$name]->getMax())
            ) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" max value "%d" must be greater than or equal to "%d".',
                    $b,
                    $name,
                    $fb[$name]->getMax(),
                    $field->getMax() ?: $field->getType()->getMax()
                ));
            }
        }
    }
}
