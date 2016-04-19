<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\SchemaDescriptor;

/**
 * Interface that must be implemented by validator constraints.
 */
interface Constraint
{
    /**
     * @param SchemaDescriptor $a
     * @param SchemaDescriptor $b
     *
     * @return string
     *
     * @throws \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b);
}
