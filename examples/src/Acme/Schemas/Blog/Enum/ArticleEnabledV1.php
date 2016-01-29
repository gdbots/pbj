<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static ArticleEnabledV1 YES()
  * @method static ArticleEnabledV1 NO()
  */
final class ArticleEnabledV1 extends Enum
{
    const YES = 1;
      const NO = 0;
  }
