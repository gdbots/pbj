<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\ToArray;

abstract class Descriptor implements ToArray, \JsonSerializable
{
    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
