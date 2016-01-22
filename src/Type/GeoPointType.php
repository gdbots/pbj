<?php

namespace Gdbots\Pbjc\Type;

final class GeoPointType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
