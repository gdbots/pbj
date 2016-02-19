<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaValidExtends implements Assert
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        if ($a->getExtends() != $b->getExtends()) {
            throw new ValidatorException(sprintf(
                'The schema "%s" must extends "%s".',
                $b,
                $a->getExtends()->getId()->toString()
            ));
        }
    }
}
