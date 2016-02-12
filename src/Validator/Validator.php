<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbjc\Validator\Exception\RuntimeException;
use Gdbots\Pbjc\Validator\Exception\ValidatorException;

/**
 * Validates values against constraints.
 */
class Validator
{
    /** @var array */
    protected $collection = [];

    /**
     * Creates a new validator.
     *
     * @return this.
     */
    public static function createValidator()
    {
        return new self();
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Adds a constraint violation to this list.
     *
     * @param mixed      $value      The value that should be validated
     * @param Constraint $constraint The constraint for the validation
     *
     * @return this
     */
    public function add($value, Constraint $constraint)
    {
        $this->collection[] = [
            'value' => $value,
            'constraint' => $constraint
        ];

        return $this;
    }

    /**
     * Validates a value against a constraint or a list of constraints.
     *
     * @thorw ValidatorException If validator doesn't exists
     * @thorw RuntimeException If a violation has occurred
     */
    public function validate()
    {
        foreach ($this->collection as $item) {
            $className = $item['constraint']->validatedBy();

            if (!class_exists($className)) {
                throw new ValidatorException(sprintf(
                    'Missing validator class "%s".',
                    $className
                ));
            }

            $validator = new $className();

            if ($violation = $validator->validate($item['value'], $item['constraint'])) {
                throw new RuntimeException($violation);
            }
        }
    }
}
