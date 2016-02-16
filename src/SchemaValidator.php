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
            new Assert\IsMixinSchemeType(),
            new Assert\SchemeContainsMixin(),
            new Assert\SchemeContainsEnum(),
            new Assert\SchemeContainsField(),
            new Assert\EnumTypeEqualTo(),
            new Assert\EnumContainsOption(),
            new Assert\FieldRequired(),
            new Assert\FieldContainsAnyOfClasses(),
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
