<?php

namespace Gdbots\Pbjc\Type;

final class MediumTextType extends AbstractStringType
{
    /**
     * {@inheritdoc}
     */
    public function getMaxBytes()
    {
        return 16777215;
    }

    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
