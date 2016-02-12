<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;

class Field extends Constraint
{
    public $message = 'This field {{ name }} should be equal to {{ compared_value }}.';
    public $ignore = [];
    public $field;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'field';
    }
}
