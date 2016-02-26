<?php

namespace Acme\Schemas\Blog\Command;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Gdbots\Schemas\Pbj\Command\CommandV1;
use Gdbots\Schemas\Pbj\Command\CommandV1Mixin;
use Gdbots\Schemas\Pbj\Command\CommandV1Trait;
use Acme\Schemas\Core\Command\UpdateEntityV1;
use Acme\Schemas\Core\Command\UpdateEntityV1Mixin;
use Acme\Schemas\Core\Command\UpdateEntityV1Trait;

final class UpdateArticleV1 extends AbstractMessage implements UpdateArticle, CommandV1, UpdateEntityV1  
{
    use CommandV1Trait;
    use UpdateEntityV1Trait;

    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        return new Schema('pbj:acme:blog:command:update-article:1-0-0', __CLASS__, [
            Fb::create('entity', T\MessageType::create())
                ->required()
                ->className('Gdbots\Schemas\Pbj\Entity')
                ->build(),
            Fb::create('user_id', T\IdentifierType::create())
                ->build()
        ], [
          CommandV1Mixin::create(), 
          UpdateEntityV1Mixin::create()
        ]);
    }
}
