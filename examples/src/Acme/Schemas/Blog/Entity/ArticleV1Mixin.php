<?php

namespace Acme\Schemas\Blog\Entity;

use Gdbots\Pbj\AbstractMixin;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\SchemaId;
use Gdbots\Pbj\Type as T;
use Gdbots\Pbj\Enum\Format;
use Gdbots\Identifiers\UuidIdentifier;
use Acme\Schemas\Blog\Enum\ArticleReasonV1;

class ArticleV1Mixin extends AbstractMixin
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
            Fb::create('slug', T\StringType::create())
                ->required()
                ->pattern('/^[A-Za-z0-9_\-]+$/')
                ->build(),
            Fb::create('title', T\TextType::create())
                ->required()
                ->build(),
            Fb::create('failed_request', T\MessageType::create())
                ->required()
                ->className('Gdbots\Schemas\Pbj\Request')
                  ->build(),
            Fb::create('failure_reason', T\StringEnumType::create())
                ->withDefault(ArticleReasonV1::EMPTY())
                ->className('Acme\Schemas\Blog\Enum\ArticleReasonV1')
                ->build()
          ];
    }
}
