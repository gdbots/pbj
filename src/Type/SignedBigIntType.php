<?php

namespace Gdbots\Pbjc\Type;

use Gdbots\Common\BigNumber;

final class SignedBigIntType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return new BigNumber(0);
    }

    /**
     * {@inheritdoc}
     */
    public function isNumeric()
    {
        return true;
    }
}
