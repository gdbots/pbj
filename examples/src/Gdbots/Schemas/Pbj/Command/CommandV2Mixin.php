<?php

namespace Gdbots\Schemas\Pbj\Command;

use Gdbots\Pbj\AbstractMixin;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\SchemaId;
use Gdbots\Pbj\Type as T;

final class CommandV2Mixin extends AbstractMixin
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return SchemaId::fromString('pbj:gdbots:pbj:mixin:command:2-0-0');
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return [
            Fb::create('command_id', T\TimeUuidType::create())
                ->required()
                ->build(),
            Fb::create('microtime', T\MicrotimeType::create())
                ->build(),
            Fb::create('correlator', T\MessageRefType::create())
                ->build(),
            Fb::create('retries', T\TinyIntType::create())
                ->build()
        ];
    }
}
