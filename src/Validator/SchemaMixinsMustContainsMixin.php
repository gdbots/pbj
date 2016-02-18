<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;

class SchemaMixinsMustContainsMixin implements Assert
{
    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        foreach ($a->getMixins() as $mixin) {
            if ($m = $b->getMixin($mixin->getId()->getCurieWithMajorRev())) {
                if (!$m->isMixinSchema()) {
                    throw new ValidatorException(sprintf(
                        'The schema "%s" mixins can only include other mixins. The schema "%s" is not a mixin.',
                        $b,
                        $mixin->getId()->toString()
                    ));
                }
            }
        }
    }
}
