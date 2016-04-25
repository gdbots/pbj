<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldValidPattern implements Constraint
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

            try {
                if ($field->getPattern() != $fb[$name]->getPattern()
                    && preg_match(sprintf('/%s/', $fb[$name]->getPattern()), null) !== false
                ) {
                    // do nothing
                }
            } catch (\Exception $e) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" pattern "%s" is invalid.',
                    $b,
                    $name,
                    $fb[$name]->getPattern()
                ));
            }
        }
    }
}
