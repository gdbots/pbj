<?php

namespace Gdbots\Pbjc\Enum;

use Gdbots\Pbj\Enum;

/**
 * @link http://spacetelescope.github.io/understanding-json-schema/reference/string.html#format
 *
 * @method static Format UNKNOWN()
 * @method static Format DATE()
 * @method static Format DATE_TIME()
 * @method static Format EMAIL()
 * @method static Format HASHTAG()
 * @method static Format HOSTNAME()
 * @method static Format IPV4()
 * @method static Format IPV6()
 * @method static Format SLUG()
 * @method static Format URI()
 * @method static Format URL()
 * @method static Format UUID()
 */
final class Format extends Enum
{
    const UNKNOWN = 'unknown';
    const DATE = 'date';
    const DATE_TIME = 'date-time';
    const EMAIL = 'email';
    const HASHTAG = 'hashtag';
    const HOSTNAME = 'hostname';
    const IPV4 = 'ipv4';
    const IPV6 = 'ipv6';
    const SLUG = 'slug';
    const URI = 'uri';
    const URL = 'url';
    const UUID = 'uuid';
}
