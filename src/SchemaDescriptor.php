<?php

namespace Gdbots\Pbjc;

final class SchemaDescriptor extends Descriptor
{
    /** @var SchemaId */
    private $id;

    /** @var FieldDescriptor[] */
    private $fields = [];

    /** @var array */
    private $options = [];

    /** @var bool */
    private $isMixin = false;

    /** @var bool */
    private $isLatestVersion = false;

    /** @var bool */
    private $isDependent = false;

    /**
     * @param SchemaId|string $id
     * @param array           $fields
     * @param array           $options
     */
    public function __construct($id, array $fields = [], array $options = [])
    {
        $this->id = $id instanceof SchemaId ? $id : SchemaId::fromString($id);

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
     * @return SchemaId
     */
    public function getId()
    {
        return $this->id;
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
     * @param FieldDescriptor $field
     */
    public function addField(FieldDescriptor $field)
    {
        if (!$this->hasField($field->getName())) {
            $this->fields[$field->getName()] = $field;
        }
    }

    /**
     * @param string $name
     *
     * @return FieldDescriptor|null
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        return;
    }

    /**
     * @return FieldDescriptor[]
     */
    public function getFields()
    {
        return $this->fields ?: $this->fields = [];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasOption($key)
    {
        return isset($this->options[$key]);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return this
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return $default;
    }

    /**
     * @param string $key
     * @param string $subkey
     * @param mixed  $value
     *
     * @return this
     */
    public function setOptionSubOption($key, $subkey, $value)
    {
        if (!isset($this->options[$key])) {
            $this->options[$key] = [];
        }
        $this->options[$key][$subkey] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param string $subkey
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getOptionSubOption($key, $subkey, $default = null)
    {
        if (isset($this->options[$key][$subkey])) {
            return $this->options[$key][$subkey];
        }

        return $default;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options ?: $this->options = [];
    }

    /**
     * @param bool $bool
     *
     * @return this
     */
    public function setIsMixin($bool)
    {
        $this->isMixin = (bool) $bool;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMixin()
    {
        return $this->isMixin;
    }

    /**
     * @param bool $bool
     *
     * @return this
     */
    public function setIsLatestVersion($bool)
    {
        $this->isLatestVersion = (bool) $bool;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLatestVersion()
    {
        return $this->isLatestVersion;
    }

    /**
     * @param bool $bool
     *
     * @return this
     */
    public function setIsDependent($bool)
    {
        $this->isDependent = (bool) $bool;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDependent()
    {
        return $this->isDependent;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'fields' => $this->fields,
            'options' => $this->options,
        ];
    }
}
