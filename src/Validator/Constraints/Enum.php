<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;

class Enum extends Constraint
{
    public $message = 'This enum {{ name }} name should be equal to {{ compared_value }}..';
    public $messageType = 'This enum {{ name }} type should be equal to {{ compared_value }}..';
    public $messageValues = 'One or more of the given enum {{ name }} values {{ value }} is expected.';
    public $enum;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'enum';
    }
}
