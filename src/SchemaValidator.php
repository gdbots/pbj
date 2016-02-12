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
            $prevSchema = SchemaParser::create($prevSchema);
        }

        Validator::createValidator()
            ->add(new Assert\IsMixinSchemeTypeConstraint())
            ->add(new Assert\RemoveSchemeMixinConstraint())
            ->add(new Assert\RemoveSchemeEnumConstraint())
            ->add(new Assert\RemoveSchemeFieldConstraint()) //todo: check inherit field from attached mixins
            ->add(new Assert\EnumTypeConstraint())
            ->add(new Assert\EnumOptionConstraint())

            //todo: additional constraints
            //->add(new Assert\RequireAddtionalFieldConstraint())
            //->add(new Assert\FieldRestrictAttributeConstraint())
            //->add(new Assert\FieldPatternConstraint())
            //->add(new Assert\FieldDefaultConstraint())
            //->add(new Assert\FieldEnumConstraint())
            //->add(new Assert\FieldAnyOfConstraint())

            // maybe add one for each field type?

            ->validate($prevSchema, $schema)
        ;
    }
}
