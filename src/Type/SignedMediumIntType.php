<?php

namespace Gdbots\Pbjc\Type;

final class SignedMediumIntType extends AbstractIntType
{
    /**
     * {@inheritdoc}
     */
    public function getMin()
    {
        return -8388608;
    }

    /**
     * {@inheritdoc}
     */
    public function getMax()
    {
        return 8388607;
    }
}
