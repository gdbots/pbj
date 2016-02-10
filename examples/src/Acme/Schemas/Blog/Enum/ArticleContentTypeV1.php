<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static ArticleContentTypeV1 UNKNOWN()
  * @method static ArticleContentTypeV1 ARTICLE()
  * @method static ArticleContentTypeV1 LINK()
  * @method static ArticleContentTypeV1 PHOTO()
  * @method static ArticleContentTypeV1 QUOTE()
  * @method static ArticleContentTypeV1 TEXT()
  * @method static ArticleContentTypeV1 VIDEO()
  */
final class ArticleContentTypeV1 extends Enum
{
    const UNKNOWN = 'unknown';
      const ARTICLE = 'article';
      const LINK = 'link';
      const PHOTO = 'photo';
      const QUOTE = 'quote';
      const TEXT = 'text';
      const VIDEO = 'video';
  }
