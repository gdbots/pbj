<?php

namespace Acme\Schemas\Blog\Entity;

use Gdbots\Pbj\AbstractMixin;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\SchemaId;
use Gdbots\Pbj\Type as T;
use Gdbots\Pbj\Enum\Format;
use Gdbots\Identifiers\UuidIdentifier;

final class GenericV1Mixin extends AbstractMixin
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return SchemaId::fromString('pbj:acme:blog:entity:generic:1-0-0');
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return [
            Fb::create('_id', T\IdentifierType::create())
                ->required()
                ->withDefault(function() {
                return UuidIdentifier::generate();
              })
                ->className('Gdbots\Identifiers\UuidIdentifier')
                ->build(),
            Fb::create('title', T\StringType::create())
                ->build(),
            Fb::create('excerpt', T\TextType::create())
                ->build(),
            Fb::create('published_at', T\MicrotimeType::create())
                ->build()
          ];
    }
}
