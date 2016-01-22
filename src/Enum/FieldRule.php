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
    const A_SINGLE_VALUE = 1;
    const A_SET = 2;
    const A_LIST = 3;
    const A_MAP = 4;
}
