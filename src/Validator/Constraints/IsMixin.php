<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;

class IsMixin extends Constraint
{
    public $message = 'This value should be equal to {{ compared_value }}.';
    public $value;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'value';
    }
}
