<?php

namespace Acme\Schemas\Blog\Entity;

use Gdbots\Pbj\AbstractMixin;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\SchemaId;
use Gdbots\Pbj\Type as T;
use Gdbots\Pbj\Enum\Format;
use Gdbots\Identifiers\UuidIdentifier;
use Acme\Schemas\Blog\Enum\ArticleContentTypeV1;
use Acme\Schemas\Blog\Enum\ArticlePublishStatusV1;

final class ArticleV1Mixin extends AbstractMixin
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return SchemaId::fromString('pbj:acme:blog:entity:article:1-0-1');
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
            Fb::create('published_at', T\MicrotimeType::create())
                ->build(),
            Fb::create('expires_at', T\TimestampType::create())
                ->build(),
            Fb::create('comments', T\MessageType::create())
                ->className('Acme\Schemas\Blog\Entity')
                  ->build()
          ];
    }
}
