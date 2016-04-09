<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Validator as Constraint;

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
            new Constraint\SchemaValidExtends(),
            new Constraint\SchemaIsMixin(),
            new Constraint\SchemaIsNotMixin(),
            new Constraint\SchemaMustContainsMixin(),
            new Constraint\SchemaMustContainsField(),
            new Constraint\SchemaMixinsMustContainsMixin(),

            new Constraint\FieldAttributeEqualTo('type'),
            new Constraint\FieldAttributeEqualTo('rule'),
            new Constraint\FieldAttributeEqualTo('format'),
            new Constraint\FieldAttributeEqualTo('precision'),
            new Constraint\FieldAttributeEqualTo('scale'),
            new Constraint\FieldAttributeEqualTo('use_type_default'),
            new Constraint\FieldAttributeEqualTo('overridable'),
            new Constraint\FieldIsRequired(),
            new Constraint\FieldIsNotRequired(),
            new Constraint\FieldValidPattern(),
            new Constraint\FieldGreaterOrEqualThan(),
            new Constraint\FieldLessOrEqualThan(),
            new Constraint\FieldMinLength(),
            new Constraint\FieldMaxLength(),
            new Constraint\FieldSameEnum(),
            new Constraint\FieldMustContainsAnyOfClasses(),
        ];
    }

    /**
     * Validates a single schema against previous version.
     *
     * @param SchemaDescriptor $schema
     *
     * @throw \RuntimeException
     */
    public function validate(SchemaDescriptor $schema)
    {
        if ($prevSchema = SchemaStore::getPreviousSchema($schema->getId())) {
            if (!$prevSchema instanceof SchemaDescriptor) {
                throw new \RuntimeException(sprintf(
                    'Un-parsed schema "%s".',
                    $prevSchema['id']
                ));
            }

            /** @var \Gdbots\Pbjc\Validator\Constraint $constraint */
            foreach ($this->constraints as $constraint) {
                $constraint->validate($prevSchema, $schema);
            }
        }

        $constraint = new Constraint\SchemaDependencyVersion();
        $constraint->validate($schema, $schema);

        $constraint = new Constraint\SchemaInheritanceFields();
        $constraint->validate($schema, $schema);
    }
}
