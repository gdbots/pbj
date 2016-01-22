<?php

namespace Gdbots\Pbjc\Type;

final class TextType extends AbstractStringType
{
    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
