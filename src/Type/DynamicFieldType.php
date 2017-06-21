<?php

namespace Gdbots\Pbjc\Type;

final class DynamicFieldType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function isScalar()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
