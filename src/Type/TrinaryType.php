<?php

namespace Gdbots\Pbjc\Type;

final class TrinaryType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return 0;
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
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getMax()
    {
        return 2;
    }

    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
