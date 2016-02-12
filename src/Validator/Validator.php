<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\SchemaDescriptor;

/**
 * Validates values against constraints.
 */
class Validator
{
    /** @var array */
    protected $constraints = [];

    /**
     * Creates a new validator.
     *
     * @return this.
     */
    public static function createValidator()
    {
        return new self();
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Adds a constraint violation to this list.
     *
     * @param ConstraintInterface $constraint The constraint for the validation
     *
     * @return this
     */
    public function add(ConstraintInterface $constraint)
    {
        $this->constraints[] = $constraint;

        return $this;
    }

    /**
     * Validates a value against a list of constraints.
     *
     * @param SchemaDescriptor $a
     * @param SchemaDescriptor $b
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        foreach ($this->constraints as $constraint) {
            $constraint->validate($a, $b);
        }
    }
}
