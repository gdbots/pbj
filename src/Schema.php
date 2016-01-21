<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\ToArray;
use Gdbots\Common\Util\StringUtils;
use Symfony\Component\HttpFoundation\ParameterBag;

final class Schema implements ToArray, \JsonSerializable
{
    /** @var SchemaId */
    private $id;

    /** @var Field[] */
    private $fields = [];

    /** @var array */
    private $mixins = [];

    /** @var ParameterBag */
    private $options = [];

    /**
     * @param SchemaId|string $id
     * @param array           $fields
     * @param array           $mixins
     * @param array           $options
     */
    public function __construct($id, array $fields = [], array $mixins = [], ParameterBag $options = null)
    {
        $this->id = $id instanceof SchemaId ? $id : SchemaId::fromString($id);

        $this->mixins = $mixins;
        $this->options = $options;

        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id->toString();
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'class_name' => $this->getClassName(),
            'fields' => $this->fields,
            'mixins' => $this->mixins,
            'options' => $this->options,
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return SchemaId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the major version qualified class name. This should be used later to
     * generate a PHP class name.
     *
     * e.g. "MessageV1"
     *
     * @return string
     */
    public function getClassName()
    {
        return sprintf('%sV%d', StringUtils::toCamelFromSlug($this->id->getMessage()), $this->id->getVersion()->getMajor());
    }

    /**
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * @param Field $field
     */
    private function addField(Field $field)
    {
        if (!$this->hasField($field->getName())) {
            $this->fields[$field->getName()] = $field;
        }
    }

    /**
     * @param string $name
     *
     * @return Field|null
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        return null;
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return array
     */
    public function getMixins()
    {
        return $this->mixins;
    }

    /**
     * @return ParameterBag
     */
    public function getOptions()
    {
        return $this->options;
    }
}
