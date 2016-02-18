<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Validator as Assert;

/**
 * Performs strict validation of the mapping schema.
 */
class SchemaValidator
{
    /** @var array */
    private $constraints = [];

    /**
     * Constructs a new validator.
     */
    public function __construct()
    {
        $this->constraints = [
            new Assert\SchemaIsMixin(),
            new Assert\SchemaIsNotMixin(),
            new Assert\SchemaMustContainsMixin(),
            new Assert\SchemaMustContainsEnum(),
            new Assert\SchemaMustContainsField(),

            new Assert\EnumTypeEqualTo(),
            new Assert\EnumMustContainsOption(),

            new Assert\FieldAttributeEqualTo('type'),
            new Assert\FieldAttributeEqualTo('rule'),
            new Assert\FieldAttributeEqualTo('format'),
            new Assert\FieldAttributeEqualTo('precision'),
            new Assert\FieldAttributeEqualTo('scale'),
            new Assert\FieldAttributeEqualTo('use_type_default'),
            new Assert\FieldAttributeEqualTo('overridable'),
            new Assert\FieldIsRequired(),
            new Assert\FieldIsNotRequired(),
            new Assert\FieldValidPattern(),
            new Assert\FieldGreaterOrEqualThan(),
            new Assert\FieldLessOrEqualThan(),
            new Assert\FieldMinLength(),
            new Assert\FieldMaxLength(),
            new Assert\FieldSameEnum(),
            new Assert\FieldMustContainsAnyOfClasses(),
        ];
    }

    /**
     * Validates a single schema against prevoius version.
     *
     * @param SchemaDescriptor $schema
     */
    public function validate(SchemaDescriptor $schema)
    {
        if (!$prevSchema = SchemaStore::getPreviousSchema($schema->getId())) {
            return [];
        }

        if (is_array($prevSchema)) {
            $prevSchema = SchemaParser::create($prevSchema);
        }

        foreach ($this->constraints as $constraint) {
            $constraint->validate($prevSchema, $schema);
        }
    }
}
