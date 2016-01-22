<?php

namespace Gdbots\Pbjc\Type;

use Gdbots\Identifiers\TimeUuidIdentifier;

final class TimeUuidType extends AbstractType
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
        return TimeUuidIdentifier::generate();
    }

    /**
     * {@inheritdoc}
     */
    public function isString()
    {
        return true;
    }
}
