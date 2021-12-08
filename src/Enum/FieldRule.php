<?php

namespace Gdbots\Pbjc\Enum;

enum FieldRule: string
{
    case A_SINGLE_VALUE = 'single';
    case A_SET = 'set';
    case A_LIST = 'list';
    case A_MAP = 'map';

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
