<?php

namespace Gdbots\Pbjc\Type;

use Brick\Math\BigInteger;

final class SignedBigIntType extends AbstractType
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
