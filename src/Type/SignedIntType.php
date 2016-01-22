<?php

namespace Gdbots\Pbjc\Type;

final class SignedIntType extends AbstractIntType
{
    /**
     * {@inheritdoc}
     */
    public function getMin()
    {
        return -2147483648;
    }

    /**
     * {@inheritdoc}
     */
    public function getMax()
    {
        return 2147483647;
    }
}
