<?php

namespace Gdbots\Pbjc\Enum;

enum TypeName: string
{
    case BIG_INT = 'big-int';
    case BINARY = 'binary';
    case BLOB = 'blob';
    case BOOLEAN = 'boolean';
    case DATE = 'date';
    case DATE_TIME = 'date-time';
    case DECIMAL = 'decimal';
    case DYNAMIC_FIELD = 'dynamic-field';
    case FLOAT = 'float';
    case GEO_POINT = 'geo-point';
    case IDENTIFIER = 'identifier';
    case INT = 'int';
    case INT_ENUM = 'int-enum';
    case MEDIUM_BLOB = 'medium-blob';
    case MEDIUM_INT = 'medium-int';
    case MEDIUM_TEXT = 'medium-text';
    case MESSAGE = 'message';
    case MESSAGE_REF = 'message-ref';
    case MICROTIME = 'microtime';
    case NODE_REF = 'node-ref';
    case SIGNED_BIG_INT = 'signed-big-int';
    case SIGNED_INT = 'signed-int';
    case SIGNED_MEDIUM_INT = 'signed-medium-int';
    case SIGNED_SMALL_INT = 'signed-small-int';
    case SIGNED_TINY_INT = 'signed-tiny-int';
    case SMALL_INT = 'small-int';
    case STRING = 'string';
    case STRING_ENUM = 'string-enum';
    case TEXT = 'text';
    case TIME_UUID = 'time-uuid';
    case TIMESTAMP = 'timestamp';
    case TINY_INT = 'tiny-int';
    case TRINARY = 'trinary';
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
