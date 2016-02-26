<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static PublishStatus DELETED()
  * @method static PublishStatus DRAFT()
  * @method static PublishStatus EXPIRED()
  * @method static PublishStatus PENDING()
  * @method static PublishStatus PUBLISHED()
  * @method static PublishStatus UNKNOWN()
  */
final class PublishStatus extends Enum
{
    const DELETED = 'deleted';
      const DRAFT = 'draft';
      const EXPIRED = 'expired';
      const PENDING = 'pending';
      const PUBLISHED = 'published';
      const UNKNOWN = 'unknown';
  }
