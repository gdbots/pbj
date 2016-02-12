<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\Validator\ConstraintInterface;
use Gdbots\Pbjc\SchemaDescriptor;

class EnumTypeConstraint implements ConstraintInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        foreach ($a->getEnums() as $enum) {
            if ($compare = $b->getEnum($enum->getName())) {
                if ($enum->getType() !== $compare->getType()) {
                    throw new ValidatorException(sprintf(
                        'The schema "%s" enum "%s" must be set with type="%s".',
                        $b,
                        $enum->getName(),
                        $enum->getType()
                    ));
                }
            }
        }
    }
}
