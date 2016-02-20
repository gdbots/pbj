<?php

namespace Acme\Schemas\Core\Command;

use Gdbots\Pbj\AbstractMixin;
use Gdbots\Pbj\FieldBuilder as Fb;
use Gdbots\Pbj\SchemaId;
use Gdbots\Pbj\Type as T;

final class UpdateEntityV2Mixin extends AbstractMixin
{
    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return SchemaId::fromString('pbj:acme:core:mixin:update-entity:2-0-0');
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return [
            Fb::create('entity', T\MessageType::create())
                ->required()
                ->className('Gdbots\Schemas\Pbj\Entity')
                  ->build(),
            Fb::create('user_id', T\IdentifierType::create())
                ->build()
          ];
    }
}
