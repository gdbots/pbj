<?php

namespace Gdbots\Pbjc\Type;

final class IdentifierType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function isString()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxBytes()
    {
        return 100;
    }
}
