<?php

namespace Gdbots\Pbjc\Type;

abstract class AbstractStringType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function isString()
    {
        return true;
    }
}
