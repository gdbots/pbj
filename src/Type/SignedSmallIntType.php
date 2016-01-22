<?php

namespace Gdbots\Pbjc\Type;

final class SignedSmallIntType extends AbstractIntType
{
    /**
     * {@inheritdoc}
     */
    public function getMin()
    {
        return -32768;
    }

    /**
     * {@inheritdoc}
     */
    public function getMax()
    {
        return 32767;
    }
}
