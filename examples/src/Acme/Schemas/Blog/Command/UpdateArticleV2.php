<?php

namespace Acme\Schemas\Blog\Command;

use Acme\Schemas\Core\Command\UpdateEntityV2;
use Acme\Schemas\Core\Command\UpdateEntityV2Mixin;
use Acme\Schemas\Core\Command\UpdateEntityV2Trait;
use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Gdbots\Schemas\Pbj\Command\CommandV1;
use Gdbots\Schemas\Pbj\Command\CommandV1Mixin;
use Gdbots\Schemas\Pbj\Command\CommandV1Trait;

final class UpdateArticleV2 extends AbstractMessage
  implements
    UpdateArticle,
    CommandV1,
    UpdateEntityV2
  
{
    use CommandV1Trait;
    use UpdateEntityV2Trait;

    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        return new Schema('pbj:acme:blog:command:update-article:2-0-0', __CLASS__,
            [
                Fb::create('entity', T\MessageType::create())
                    ->required()
                    ->className('Gdbots\Schemas\Pbj\Entity\Entity')
                    ->build(),
                Fb::create('user_id', T\IdentifierType::create())
                    ->build()
            ],
            [
                CommandV1Mixin::create(), 
                UpdateEntityV2Mixin::create()
            ]
        );
    }
}
