<?php

namespace Gdbots\Pbjc\Type;

abstract class AbstractIntType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isNumeric()
    {
        return true;
    }
}
