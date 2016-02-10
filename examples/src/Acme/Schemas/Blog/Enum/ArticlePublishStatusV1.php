<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static ArticlePublishStatusV1 PUBLISHED()
  * @method static ArticlePublishStatusV1 DRAFT()
  * @method static ArticlePublishStatusV1 PENDING()
  * @method static ArticlePublishStatusV1 EXPIRED()
  * @method static ArticlePublishStatusV1 DELETED()
  */
final class ArticlePublishStatusV1 extends Enum
{
    const PUBLISHED = 'published';
      const DRAFT = 'draft';
      const PENDING = 'pending';
      const EXPIRED = 'expired';
      const DELETED = 'deleted';
  }
