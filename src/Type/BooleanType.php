<?php

namespace Gdbots\Pbjc\Type;

final class BooleanType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return false;
    }

    /**
     * @see Type::isBoolean
     */
    public function isBoolean()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
