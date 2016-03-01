<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Common\Enum;
use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\Type\Type;
use Gdbots\Pbjc\SchemaDescriptor;

class FieldAttributeEqualTo implements Constraint
{
    /** @var string */
    private $attribute;

    /**
     * @param string $attribute
     */
    public function __construct($attribute)
    {
        $this->attribute = $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(SchemaDescriptor $a, SchemaDescriptor $b)
    {
        $fa = array_merge($a->getInheritedFields(), $a->getFields());
        $fb = array_merge($b->getInheritedFields(), $b->getFields());

        foreach ($fa as $name => $field) {
            if (!isset($fb[$name])) {
                continue;
            }

            $method = 'get'.StringUtils::toCamelFromSnake($this->attribute);
            if (!method_exists($field, $method)) {
                $method = 'is'.StringUtils::toCamelFromSnake($this->attribute);
                if (!method_exists($field, $method)) {
                    throw new \RuntimeException(sprintf('Invalid FieldDescriptor attribute "%s"', $this->attribute));
                }
            }

            if ($field->$method() != $fb[$name]->$method()) {
                $value = $field->$method();

                if ($value instanceof Enum) {
                    $value = $value->__toString();
                }

                if ($value instanceof Type) {
                    $value = $value->getTypeName()->__toString();
                }

                throw new ValidatorException(sprintf(
                    'The schema "%s" field "%s" should be of %s "%s".',
                    $b,
                    $name,
                    $this->attribute,
                    $value
                ));
            }
        }
    }
}
