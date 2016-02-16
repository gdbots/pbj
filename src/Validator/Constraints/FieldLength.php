<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\Validator\ConstraintInterface;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldLength implements ConstraintInterface
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

            $mina = $field->getType() instanceof StringType ? $field->getMinLength() : $field->getMin();
            $minb = $fb[$name]->getType() instanceof StringType ? $fb[$name]->getMinLength() : $fb[$name]->getMin();

            if ((int) $mina < (int) $minb) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" min must be greater than or equal to "%d".',
                    $b,
                    $name,
                    (int) $mina
                ));
            }

            $maxa = $field->getType() instanceof StringType ? $field->getMaxLength() : $field->getMax();
            $maxb = $fb[$name]->getType() instanceof StringType ? $fb[$name]->getMaxLength() : $fb[$name]->getMax();

            if ((int) $maxa > (int) $maxb) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" max must be less than or equal to "%d".',
                    $b,
                    $name,
                    (int) $maxa
                ));
            }
        }
    }
}
