<?php

namespace Acme\Schemas\Blog\Entity;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Acme\Schemas\Blog\Enum\PublishStatus;
use Acme\Schemas\Blog\Enum\ContentType;
use Gdbots\Schemas\Pbj\Entity\EntityV1;
use Gdbots\Schemas\Pbj\Entity\EntityV1Mixin;
use Gdbots\Schemas\Pbj\Entity\EntityV1Trait;
use Acme\Schemas\Blog\HasCommentsV1;
use Acme\Schemas\Blog\HasCommentsV1Mixin;
use Acme\Schemas\Blog\HasCommentsV1Trait;
use Gdbots\Pbj\Enum\Format;

final class ArticleV1 extends AbstractMessage implements Article, EntityV1, HasCommentsV1  
{
    use EntityV1Trait;
    use HasCommentsV1Trait;

    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        return new Schema('pbj:acme:blog:entity:article:1-0-1', __CLASS__, [
            Fb::create('title', T\StringType::create())
                ->build(),
            Fb::create('excerpt', T\TextType::create())
                ->build(),
            Fb::create('excerpt_html', T\TextType::create())
                ->build(),
            Fb::create('thumbnails', T\StringType::create())
                ->asAMap()
                ->format(Format::URL())
                ->build(),
            Fb::create('private', T\BooleanType::create())
                ->build(),
            Fb::create('publish_status', T\StringEnumType::create())
                ->withDefault(PublishStatus::DRAFT())
                ->className('Acme\Schemas\Blog\Enum\PublishStatus')
                ->build(),
            Fb::create('content_type', T\StringEnumType::create())
                ->withDefault(ContentType::UNKNOWN())
                ->className('Acme\Schemas\Blog\Enum\ContentType')
                ->build(),
            Fb::create('expires_at', T\TimestampType::create())
                ->build(),
            Fb::create('comments', T\MessageRefType::create())
                ->asAList()
                ->build()
        ], [
            EntityV1Mixin::create(), 
            HasCommentsV1Mixin::create()
        ]);
    }
}
