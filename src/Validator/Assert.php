<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\SchemaDescriptor;

/**
 * Interface that must be implemented by validator asserts.
 */
interface Assert
{
    /**
     * @param SchemaDescriptor $a
     * @param SchemaDescriptor $b
     *
     * @return string
     *
     * @throw \Gdbots\Pbjc\Exception\ValidatorException
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b);
}
