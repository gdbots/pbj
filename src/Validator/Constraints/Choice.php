<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;

class Choice extends Constraint
{
    public $message = 'One or more of the given values {{ value }} is expected.';
    public $choices;

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'choices';
    }
}
