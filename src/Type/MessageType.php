<?php

namespace Gdbots\Pbjc\Type;

final class MessageType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function isMessage()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return false;
    }
}
