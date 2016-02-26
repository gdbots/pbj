<?php

namespace Gdbots\Pbjc;

final class EnumDescriptor
{
    use LanguageDescriptorTrait;

    /** @var EnumId */
    private $id;

    /** @var string */
    private $type;

    /** @var array */
    private $values = [];

    /**
     * @param EnumId|string $id
     * @param string        $type
     * @param array         $values
     */
    public function __construct($id, $type, array $values)
    {
        $this->id = $id instanceof EnumId ? $id : EnumId::fromString($id);
        $this->type = $type;
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->id->toString();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return SchemaId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values ?: $this->values = [];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasValue($key)
    {
        return isset($this->values[$key]);
    }
}
