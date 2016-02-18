<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldMustContainsAnyOfClasses implements Assert
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        $fa = array_merge($a->getInheritedFields(), $a->getFields());
        $fb = array_merge($b->getInheritedFields(), $b->getFields());

        foreach ($fa as $name => $field) {
            if (!isset($fb[$name]) || count($field->getAnyOf()) === 0) {
                continue;
            }

            $aoa = [];
            foreach ($field->getAnyOf() as $schema) {
                if (!in_array($schema->getId()->getCurie(), $aoa)) {
                    $aoa[] = $schema->getId()->getCurie();
                }
            }

            $aob = [];
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
