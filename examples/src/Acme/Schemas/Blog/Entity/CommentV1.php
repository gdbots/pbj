<?php

namespace Acme\Schemas\Blog\Entity;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Gdbots\Identifiers\UuidIdentifier;
use Gdbots\Schemas\Pbj\Entity\EntityV1;
use Gdbots\Schemas\Pbj\Entity\EntityV1Mixin;
use Gdbots\Schemas\Pbj\Entity\EntityV1Trait;

final class CommentV1 extends AbstractMessage implements Comment, EntityV1  
{
    use EntityV1Trait;

    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        return new Schema('pbj:acme:blog:entity:comment:1-0-0', __CLASS__,
          [
                Fb::create('_id', T\IdentifierType::create())
                    ->required()
                    ->withDefault(function() { return UuidIdentifier::generate(); })
                    ->className('Gdbots\Identifiers\UuidIdentifier')
                    ->build(),
                Fb::create('comment', T\TextType::create())
                    ->build(),
                Fb::create('published_at', T\MicrotimeType::create())
                    ->build()
          ], [
              EntityV1Mixin::create()
          ]
        );
    }

    
}
