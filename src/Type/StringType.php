<?php

namespace Gdbots\Pbjc\Type;

final class StringType extends AbstractStringType
{
    /**
     * {@inheritdoc}
     */
    public function getMaxBytes()
    {
        return 255;
    }
}
