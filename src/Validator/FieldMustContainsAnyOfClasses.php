<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldMustContainsAnyOfClasses implements Constraint
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
            if (!isset($fb[$name]) || count($field->getAnyOf()) === 0) {
                continue;
            }

            $aoa = [];
            /** @var SchemaDescriptor $schema*/
            foreach ($field->getAnyOf() as $schema) {
                if (!in_array($schema->getId()->getCurie(), $aoa)) {
                    $aoa[] = $schema->getId()->getCurie();
                }
            }

            $aob = [];
            /** @var SchemaDescriptor $schema*/
            foreach ($fb[$name]->getAnyOf() as $schema) {
                if (!in_array($schema->getId()->getCurie(), $aob)) {
                    $aob[] = $schema->getId()->getCurie();
                }
            }

            $diff = array_diff($aoa, $aob);
            if (count($diff)) {
                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" must include the following anyOf class(es): "%s".',
                    $b,
                    $name,
                    implode('", "', $diff)
                ));
            }
        }
    }
}
