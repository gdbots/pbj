<?php

namespace Acme\Schemas\Blog\Entity;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Gdbots\Pbj\Enum\Format;
use Gdbots\Identifiers\UuidIdentifier;

final class GenericV1 extends AbstractMessage implements Generic{

    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        return new Schema('pbj:acme:blog:entity:generic:1-0-0', __CLASS__, [
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
          ], []);
    }
}
