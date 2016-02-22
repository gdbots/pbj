<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static PublishStatus PUBLISHED()
  * @method static PublishStatus DRAFT()
  * @method static PublishStatus PENDING()
  * @method static PublishStatus EXPIRED()
  * @method static PublishStatus DELETED()
  */
final class PublishStatus extends Enum
{
    const PUBLISHED = 'published';
      const DRAFT = 'draft';
      const PENDING = 'pending';
      const EXPIRED = 'expired';
      const DELETED = 'deleted';
  }
