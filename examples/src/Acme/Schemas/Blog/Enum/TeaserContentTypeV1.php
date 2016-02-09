<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static TeaserContentTypeV1 UNKNOWN()
  * @method static TeaserContentTypeV1 ARTICLE()
  * @method static TeaserContentTypeV1 AUDIO()
  * @method static TeaserContentTypeV1 COLLECTION()
  * @method static TeaserContentTypeV1 LINK()
  * @method static TeaserContentTypeV1 PHOTO()
  * @method static TeaserContentTypeV1 POLL()
  * @method static TeaserContentTypeV1 PROFILE()
  * @method static TeaserContentTypeV1 QUOTE()
  * @method static TeaserContentTypeV1 SOLICIT()
  * @method static TeaserContentTypeV1 TEXT()
  * @method static TeaserContentTypeV1 VIDEO()
  */
final class TeaserContentTypeV1 extends Enum
{
    const UNKNOWN = 'unknown';
      const ARTICLE = 'article';
      const AUDIO = 'audio';
      const COLLECTION = 'collection';
      const LINK = 'link';
      const PHOTO = 'photo';
      const POLL = 'poll';
      const PROFILE = 'profile';
      const QUOTE = 'quote';
      const SOLICIT = 'solicit';
      const TEXT = 'text';
      const VIDEO = 'video';
  }
