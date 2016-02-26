<?php

namespace Gdbots\Schemas\Pbj\Entity;

use Gdbots\Identifiers\UuidIdentifier;
use Gdbots\Pbj\AbstractMixin;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\SchemaId;
use Gdbots\Pbj\Type as T;

final class EntityV1Mixin extends AbstractMixin
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return SchemaId::fromString('pbj:gdbots:pbj:mixin:entity:1-0-0');
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return [
            Fb::create('_id', T\IdentifierType::create())
                ->required()
                ->withDefault(function() { return UuidIdentifier::generate(); })
                ->className('Gdbots\Identifiers\UuidIdentifier')
                ->overridable(true)
                ->build(),
            Fb::create('etag', T\StringType::create())
                ->pattern('/^[A-Za-z0-9_\-]+$/')
                ->build(),
            Fb::create('created_at', T\MicrotimeType::create())
                ->build(),
            Fb::create('updated_at', T\MicrotimeType::create())
                ->build()
        ];
    }
}
