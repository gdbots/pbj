<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Validator\Constraints as Assert;

/**
 * Performs strict validation of the mapping schema.
 */
class SchemaValidator
{
    /** @var SchemaValidator */
    private static $instance;

    /** @var array */
    private $constraints = [];

    /**
     * Constructs a new validator.
     */
    public function __construct()
    {
        $this->constraints = [
            new Assert\IsMixinSchemeTypeConstraint(),
            new Assert\RemoveSchemeMixinConstraint(),
            new Assert\RemoveSchemeEnumConstraint(),
            new Assert\RemoveSchemeFieldConstraint(),
            new Assert\EnumTypeConstraint(),
            new Assert\EnumOptionConstraint(),
            new Assert\RequireAddtionalFieldConstraint(),
            new Assert\FieldRestrictAttributeConstraint(),
            new Assert\FieldPatternConstraint(),
            new Assert\FieldDefaultConstraint(),
            new Assert\FieldEnumConstraint(),
            new Assert\FieldAnyOfConstraint(),
        ];
    }

    /**
     * @return this
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
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
