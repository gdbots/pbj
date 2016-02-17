<?php

namespace Gdbots\Pbjc\Assert;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class EnumTypeEqualTo implements Assert
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
                        'The schema "%s" enum "%s" should be of type "%s".',
                        $b,
                        $enum->getName(),
                        $enum->getType()
                    ));
                }
            }
        }
    }
}
