<?php

namespace Gdbots\Pbjc\Validator;

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
