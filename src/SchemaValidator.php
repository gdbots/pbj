<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Validator\Validator;
use Gdbots\Pbjc\Validator\Constraints as Assert;

/**
 * Performs strict validation of the mapping schema.
 */
class SchemaValidator
{
    /**
     * Validates a single schema against prevoius version.
     *
     * @param SchemaDescriptor $schema
     */
    public static function validateMapping(SchemaDescriptor $schema)
    {
        if (!$prevSchema = SchemaStore::getPreviousSchema($schema->getId())) {
            return [];
        }

        if (is_array($prevSchema)) {
            $prevSchema = self::create($prevSchema);
        }

        Validator::createValidator()
            ->add(new Assert\IsMixinSchemeTypeConstraint())
            ->add(new Assert\RemoveSchemeMixinConstraint())
            ->add(new Assert\RemoveSchemeEnumConstraint())
            ->add(new Assert\RemoveSchemeFieldConstraint())
            ->add(new Assert\EnumTypeConstraint())
            ->add(new Assert\EnumOptionConstraint())

            //todo: additional constraints

            ->validate($prevSchema, $schema)
        ;
    }
}
