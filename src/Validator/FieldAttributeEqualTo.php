<?php

namespace Gdbots\Pbjc\Validator;

use Gdbots\Pbj\Enum;
use Gdbots\Pbj\Util\StringUtil;
use Gdbots\Pbjc\Exception\ValidatorException;
use Gdbots\Pbjc\SchemaDescriptor;
use Gdbots\Pbjc\Type\Type;

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

        /** @var \Gdbots\Pbjc\FieldDescriptor $field */
        /** @var \Gdbots\Pbjc\FieldDescriptor[] $fb */
        foreach ($fa as $name => $field) {
            if (!isset($fb[$name])) {
                continue;
            }

            $method = 'get' . StringUtil::toCamelFromSnake($this->attribute);
            if (!method_exists($field, $method)) {
                $method = 'is' . StringUtil::toCamelFromSnake($this->attribute);
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

                if ($value === true) {
                    $value = 'true';
                }
                if ($value === false) {
                    $value = 'false';
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
