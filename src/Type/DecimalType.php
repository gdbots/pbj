<?php

namespace Gdbots\Pbjc\Type;

final class DecimalType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return 0.0;
    }

    /**
     * {@inheritdoc}
     */
    public function isNumeric()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMin()
    {
        return -1;
    }

    /**
     * {@inheritdoc}
     */
    public function getMax()
    {
        return INF;
    }
}
