<?php

namespace Gdbots\Pbjc;

final class EnumDescriptor extends Descriptor
{
    /** @var name */
    private $name;

    /** @var array */
    private $values = [];

    /**
     * @param string $name
     * @param array  $values
     */
    public function __construct($name, array $values)
    {
        $this->name = $name;
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
     * @return array
     */
    public function getValues()
    {
        return $this->values ?: $this->values = [];
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'name' => $this->name,
            'values' => $this->values,
        ];
    }
}
