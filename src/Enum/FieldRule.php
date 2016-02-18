<?php

namespace Gdbots\Pbjc\Enum;

use Gdbots\Common\Enum;

/**
 * @method static FieldRule A_SINGLE_VALUE()
 * @method static FieldRule A_SET()
 * @method static FieldRule A_LIST()
 * @method static FieldRule A_MAP()
 */
final class FieldRule extends Enum
{
    const A_SINGLE_VALUE = 'simple';
    const A_SET = 'set';
    const A_LIST = 'list';
    const A_MAP = 'map';
}
