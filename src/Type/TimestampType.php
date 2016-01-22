<?php

namespace Gdbots\Pbjc\Type;

final class TimestampType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return time();
    }

    /**
     * {@inheritdoc}
     */
    public function isNumeric()
    {
        return true;
    }
}
