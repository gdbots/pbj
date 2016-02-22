<?php

namespace Acme\Schemas\Blog\Command;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Gdbots\Schemas\Pbj\Command\CommandV2;
use Gdbots\Schemas\Pbj\Command\CommandV2Mixin;
use Gdbots\Schemas\Pbj\Command\CommandV2Trait;
use Acme\Schemas\Core\Command\UpdateEntityV2;
use Acme\Schemas\Core\Command\UpdateEntityV2Mixin;
use Acme\Schemas\Core\Command\UpdateEntityV2Trait;

final class UpdateArticleV2 extends AbstractMessage implements UpdateArticle, CommandV2, UpdateEntityV2  
{
    use CommandV2Trait;
        use UpdateEntityV2Trait;
    
    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        return new Schema('pbj:acme:blog:command:update-article:2-0-0', __CLASS__, [
            Fb::create('entity', T\MessageType::create())
                ->required()
                ->className('Gdbots\Schemas\Pbj\Entity')
                  ->build(),
            Fb::create('user_id', T\IdentifierType::create())
                ->build()
          ], [
                      CommandV2Mixin::create(), 
                      UpdateEntityV2Mixin::create()
          ]);
    }
}
