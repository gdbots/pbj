<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\Validator\ConstraintInterface;
use Gdbots\Pbjc\SchemaDescriptor;

class IsMixinSchemeType implements ConstraintInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        if ($a->isMixinSchema() !== $b->isMixinSchema()) {
            throw new ValidatorException(sprintf(
                'The schema "%s" must be set with mixin="%s".',
                $b,
                $a->isMixinSchema() ? 'true' : 'false'
            ));
        }
    }
}
