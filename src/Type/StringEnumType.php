<?php

namespace Gdbots\Pbjc\Type;

final class StringEnumType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function isString()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxBytes()
    {
        return 100;
    }
}
