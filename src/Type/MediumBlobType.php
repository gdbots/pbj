<?php

namespace Gdbots\Pbjc\Type;

final class MediumBlobType extends AbstractBinaryType
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
