<?php

namespace Gdbots\Pbjc\Type;

final class BlobType extends AbstractBinaryType
{
    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
