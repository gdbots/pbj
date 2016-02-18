<?php

namespace Gdbots\Pbjc;

final class EnumDescriptor
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var array */
    private $values = [];

    /**
     * @param string $name
     * @param string $type
     * @param array  $values
     */
    public function __construct($name, $type, array $values)
    {
        $this->name = $name;
        $this->type = $type;
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
}
