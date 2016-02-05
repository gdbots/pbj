<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\ToArray;

final class Enum implements ToArray, \JsonSerializable
{
    /** @var name */
    private $name = [];

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
     * @param string $key
     *
     * @return bool
     */
    public function hasValue($key)
    {
        return isset($this->values[$key]);
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return this
     */
    public function setValue($key, $value)
    {
        $this->values[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getValue($key, $default = null)
    {
        if (isset($this->values[$key])) {
            return $this->values[$key];
        }

        return $default;
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
            'name'   => $this->name,
            'values' => $this->values
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
