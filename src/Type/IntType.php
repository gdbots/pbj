<?php

namespace Gdbots\Pbjc\Type;

final class IntType extends AbstractIntType
{
    /**
     * {@inheritdoc}
     */
    public function getMin()
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getMax()
    {
        return 4294967295;
    }
}
