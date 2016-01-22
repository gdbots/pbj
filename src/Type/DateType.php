<?php

namespace Gdbots\Pbjc\Type;

final class DateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
