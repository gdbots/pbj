<?php

namespace Gdbots\Pbjc\Type;

final class SignedTinyIntType extends AbstractIntType
{
    /**
     * {@inheritdoc}
     */
    public function getMin()
    {
        return -128;
    }

    /**
     * {@inheritdoc}
     */
    public function getMax()
    {
        return 127;
    }
}
