<?php

namespace Gdbots\Pbjc\Type;

use Brick\Math\BigInteger;

final class BigIntType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return BigInteger::zero();
    }

    /**
     * {@inheritdoc}
     */
    public function isNumeric()
    {
        return true;
    }
}
