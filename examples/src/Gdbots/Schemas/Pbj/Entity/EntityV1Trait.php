<?php

namespace Gdbots\Schemas\Pbj\Entity;

use Gdbots\Pbj\Schema;
use Gdbots\Pbj\MessageRef;

/**
 * @method static Schema schema
 * @method mixed get($fieldName, $default = null)
 */
trait EntityV1Trait
{
    /**
     * @param string $tag
     * @return MessageRef
     */
    public function generateMessageRef($tag = null)
    {
        return new MessageRef(static::schema()->getCurie(), $this->get('_id'), $tag);
    }
}
