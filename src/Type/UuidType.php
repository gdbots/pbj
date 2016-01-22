<?php

namespace Gdbots\Pbjc\Type;

use Gdbots\Identifiers\UuidIdentifier;

final class UuidType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function isScalar()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return UuidIdentifier::generate();
    }

    /**
     * {@inheritdoc}
     */
    public function isString()
    {
        return true;
    }
}
