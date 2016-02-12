<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\Validator\ConstraintInterface;
use Gdbots\Pbjc\SchemaDescriptor;

class EnumOptionConstraint implements ConstraintInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        foreach ($a->getEnums() as $enum) {
            if ($compare = $b->getEnum($enum->getName())) {
                $diff = array_diff(
                    array_keys($enum->getValues()),
                    array_keys($compare->getValues())
                );
                if (count($diff)) {
                    throw new ValidatorException(sprintf(
                        'The schema "%s" enum "%s" must include the following option(s): "%s".',
                        $b,
                        $enum->getName(),
                        implode('", "', $diff)
                    ));
                }
            }
        }
    }
}
