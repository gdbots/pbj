<?php

namespace Acme\Schemas\Blog\Entity;

use Gdbots\Pbj\AbstractMessage;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\Schema;
use Gdbots\Pbj\Type as T;
use Gdbots\Pbj\Enum\Format;
use Acme\Schemas\Blog\Enum\ArticleContentTypeV1;
use Acme\Schemas\Blog\Enum\ArticlePublishStatusV1;
use Acme\Schemas\Blog\Entity\GenericV1;
use Acme\Schemas\Blog\Entity\GenericV1Mixin;
use Acme\Schemas\Blog\Entity\GenericV1Trait;

final class ArticleV1 extends AbstractMessage implements Article, GenericV1  
{
    use GenericV1Trait;
    
    /**
     * @return Schema
     */
    protected static function defineSchema()
    {
        return new Schema('pbj:acme:blog:entity:article:1-0-1', __CLASS__, [
            Fb::create('title', T\StringType::create())
                ->pattern('/^[A-Za-z0-9_\-]+$/')
                ->max(100)
                ->build(),
            Fb::create('excerpt_html', T\TextType::create())
                ->build(),
            Fb::create('thumbnails', T\StringType::create())
                ->asAMap()
                ->format(Format::URL())
                ->build(),
            Fb::create('private', T\BooleanType::create())
                ->build(),
            Fb::create('content_type', T\StringEnumType::create())
                ->withDefault(ArticleContentTypeV1::UNKNOWN())
                ->className('Acme\Schemas\Blog\Enum\ArticleContentTypeV1')
                ->build(),
            Fb::create('publish_status', T\StringEnumType::create())
                ->withDefault(ArticlePublishStatusV1::DRAFT())
                ->className('Acme\Schemas\Blog\Enum\ArticlePublishStatusV1')
                ->build(),
            Fb::create('expires_at', T\TimestampType::create())
                ->build(),
            Fb::create('comments', T\MessageRefType::create())
                ->asAList()
                ->className('Acme\Schemas\Blog\Entity')
                  ->build()
          ], [GenericV1Mixin::create()]);
    }
}
