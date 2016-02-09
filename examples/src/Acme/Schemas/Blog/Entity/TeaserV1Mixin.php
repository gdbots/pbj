<?php

namespace Acme\Schemas\Blog\Entity;

use Gdbots\Pbj\AbstractMixin;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\SchemaId;
use Gdbots\Pbj\Type as T;
use Gdbots\Pbj\Enum\Format;
use Wb\Teasers\TeaserId;
use Acme\Schemas\Blog\Enum\TeaserPublishStatusV1;
use Acme\Schemas\Blog\Enum\TeaserContentTypeV1;
use Wb\Teasers\Entity\Teaserable;

final class TeaserV1Mixin extends AbstractMixin
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return SchemaId::fromString('pbj:acme:blog:entity:teaser:1-0-1');
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
                return TeaserId::generate();
              })
                ->className('Wb\Teasers\TeaserId')
                ->build(),
            Fb::create('publish_status', T\StringEnumType::create())
                ->withDefault(TeaserPublishStatusV1::DRAFT())
                ->className('Acme\Schemas\Blog\Enum\TeaserPublishStatusV1')
                ->build(),
            Fb::create('published_at', T\MicrotimeType::create())
                ->build(),
            Fb::create('expires_at', T\TimestampType::create())
                ->build(),
            Fb::create('target_curie', T\StringType::create())
                ->pattern('/^[a-z0-9-]+:[a-z0-9-]+$/')
                ->build(),
            Fb::create('target_node_ref', T\StringType::create())
                ->pattern('/^[a-z0-9-]+:[a-z0-9\/-]+$/')
                ->build(),
            Fb::create('target_published_at', T\TimestampType::create())
                ->build(),
            Fb::create('private', T\BooleanType::create())
                ->build(),
            Fb::create('content_type', T\StringEnumType::create())
                ->withDefault(TeaserContentTypeV1::UNKNOWN())
                ->className('Acme\Schemas\Blog\Enum\TeaserContentTypeV1')
                ->build(),
            Fb::create('title', T\StringType::create())
                ->build(),
            Fb::create('short_title', T\StringType::create())
                ->build(),
            Fb::create('tiny_title', T\StringType::create())
                ->build(),
            Fb::create('excerpt', T\TextType::create())
                ->build(),
            Fb::create('excerpt_html', T\TextType::create())
                ->build(),
            Fb::create('icon_label', T\StringType::create())
                ->build(),
            Fb::create('read_more_label', T\StringType::create())
                ->build(),
            Fb::create('og_title', T\StringType::create())
                ->build(),
            Fb::create('og_description', T\TextType::create())
                ->build(),
            Fb::create('seo_title', T\StringType::create())
                ->build(),
            Fb::create('seo_keywords', T\StringType::create())
                ->asASet()
                ->build(),
            Fb::create('target_title', T\StringType::create())
                ->build(),
            Fb::create('channel', T\StringType::create())
                ->format(Format::SLUG())
                ->build(),
            Fb::create('channel_title', T\StringType::create())
                ->build(),
            Fb::create('hashtags', T\StringType::create())
                ->asASet()
                ->format(Format::HASHTAG())
                ->build(),
            Fb::create('internal_hashtags', T\StringType::create())
                ->asASet()
                ->format(Format::HASHTAG())
                ->build(),
            Fb::create('lists', T\StringType::create())
                ->asASet()
                ->format(Format::SLUG())
                ->build(),
            Fb::create('galleries', T\StringType::create())
                ->asASet()
                ->format(Format::SLUG())
                ->build(),
            Fb::create('thumbnails', T\StringType::create())
                ->asAMap()
                ->format(Format::URL())
                ->build(),
            Fb::create('sponsor', T\StringType::create())
                ->format(Format::URL())
                ->build(),
            Fb::create('img_credit', T\StringType::create())
                ->build(),
            Fb::create('img_caption', T\TextType::create())
                ->build(),
            Fb::create('geo_location', T\GeoPointType::create())
                ->build(),
            Fb::create('search_text', T\TextType::create())
                ->build(),
            Fb::create('sync_with_target', T\BooleanType::create())
                ->build(),
            Fb::create('target', T\MessageType::create())
                ->className('Wb\Teasers\Entity\Teaserable')
                ->build()
          ];
    }
}
