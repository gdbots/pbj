<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static TeaserPublish_statusV1 PUBLISHED()
  * @method static TeaserPublish_statusV1 DRAFT()
  * @method static TeaserPublish_statusV1 PENDING()
  * @method static TeaserPublish_statusV1 EXPIRED()
  * @method static TeaserPublish_statusV1 DELETED()
  */
final class TeaserPublish_statusV1 extends Enum
{
    const PUBLISHED = 'published';
      const DRAFT = 'draft';
      const PENDING = 'pending';
      const EXPIRED = 'expired';
      const DELETED = 'deleted';
  }
