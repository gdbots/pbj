<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static ArticleReasonV1 EMPTY()
  * @method static ArticleReasonV1 INVALID()
  * @method static ArticleReasonV1 DELETED()
  */
final class ArticleReasonV1 extends Enum
{
    const EMPTY = 'empty';
      const INVALID = 'invalid';
      const DELETED = 'deleted';
  }
