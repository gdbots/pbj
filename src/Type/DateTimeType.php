<?php

namespace Gdbots\Pbjc\Type;

final class DateTimeType extends AbstractType
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
    public function allowedInSet()
    {
        return false;
    }
}
