<?php

namespace Gdbots\Schemas\Pbj\Command;

use Gdbots\Pbj\MessageRef;
use Gdbots\Pbj\Schema;

/**
 * @method static Schema schema
 * @method mixed get($fieldName, $default = null)
 */
trait CommandV2Trait
{
    /**
 * @param string $tag
 * @return MessageRef
 */
public function generateMessageRef($tag = null)
{
    return new MessageRef(static::schema()->getCurie(), $this->get('command_id'), $tag);
}

/**
 * @return array
 */
public function getUriTemplateVars()
{
    return [
        'command_id' => (string)$this->get('command_id'),
        'microtime' => (string)$this->get('microtime'),
    ];
}
}
