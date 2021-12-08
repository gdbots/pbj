<?php

namespace Gdbots\Pbjc\Enum;

enum Format: string
{
    case UNKNOWN = 'unknown';
    case DATE = 'date';
    case DATE_TIME = 'date-time';
    case EMAIL = 'email';
    case HASHTAG = 'hashtag';
    case HOSTNAME = 'hostname';
    case IPV4 = 'ipv4';
    case IPV6 = 'ipv6';
    case SLUG = 'slug';
    case URI = 'uri';
    case URL = 'url';
    case UUID = 'uuid';

    public static function create(string $value): self
    {
        return self::from($value);
    }

    public static function values(): array
    {
        $a = [];
        foreach (self::cases() as $c) {
            $a[$c->name] = $c->value;
        }
        return $a;
    }
}
