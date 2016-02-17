<?php

namespace Gdbots\Pbjc\Assert;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemeIsMixin implements Assert
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        if ($a->isMixinSchema() && !$b->isMixinSchema()) {
            throw new ValidatorException(sprintf(
                'The schema "%s" must be a mixin.',
                $b
            ));
        }
    }
}
