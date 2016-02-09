<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static TeaserPublishStatusV1 PUBLISHED()
  * @method static TeaserPublishStatusV1 DRAFT()
  * @method static TeaserPublishStatusV1 PENDING()
  * @method static TeaserPublishStatusV1 EXPIRED()
  * @method static TeaserPublishStatusV1 DELETED()
  */
final class TeaserPublishStatusV1 extends Enum
{
    const PUBLISHED = 'published';
      const DRAFT = 'draft';
      const PENDING = 'pending';
      const EXPIRED = 'expired';
      const DELETED = 'deleted';
  }
