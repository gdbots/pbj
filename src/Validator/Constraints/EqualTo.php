<?php

namespace Gdbots\Pbjc\Validator\Constraints;

class EqualTo extends AbstractComparison
{
    public $message = 'This value should be equal to {{ compared_value }}.';
}
