<?php

namespace Acme\Schemas\Blog\Enum;

use Gdbots\Common\Enum;

/**
  * @method static TeaserContent_typeV1 UNKNOWN()
  * @method static TeaserContent_typeV1 ARTICLE()
  * @method static TeaserContent_typeV1 AUDIO()
  * @method static TeaserContent_typeV1 COLLECTION()
  * @method static TeaserContent_typeV1 LINK()
  * @method static TeaserContent_typeV1 PHOTO()
  * @method static TeaserContent_typeV1 POLL()
  * @method static TeaserContent_typeV1 PROFILE()
  * @method static TeaserContent_typeV1 QUOTE()
  * @method static TeaserContent_typeV1 SOLICIT()
  * @method static TeaserContent_typeV1 TEXT()
  * @method static TeaserContent_typeV1 VIDEO()
  */
final class TeaserContent_typeV1 extends Enum
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
