<?php

namespace Gdbots\Pbjc\Type;

final class MediumIntType extends AbstractIntType
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
        return 16777215;
    }
}
