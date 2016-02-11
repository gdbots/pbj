<?php

namespace Gdbots\Pbjc\Validator\Constraints;

use Gdbots\Pbjc\Validator\Constraint;
use Gdbots\Pbjc\Validator\Exception\ConstraintDefinitionException;

/**
 * Used for the comparison of values.
 */
abstract class AbstractComparison extends Constraint
{
    public $message;
    public $value;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        if (is_array($options) && !isset($options['value'])) {
            throw new ConstraintDefinitionException(sprintf(
                'The %s constraint requires the "value" option to be set.',
                get_class($this)
            ));
        }

        parent::__construct($options);
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultOption()
    {
        return 'value';
    }
}
