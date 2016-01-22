<?php

namespace Gdbots\Pbjc\Type;

abstract class AbstractBinaryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function isBinary()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isString()
    {
        return true;
    }
}
