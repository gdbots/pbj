<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Exception\InvalidEnumId;

/**
 * Enum Id Format:
 *  vendor:package:name.
 *
 * Formats:
 *  VENDOR:  [a-z0-9-]+
 *  PACKAGE: [a-z0-9\.-]+
 *  NAME:    [a-z0-9-]+
 *
 * Examples of fully qualified schema ids:
 *  acme:videos:format
 *  acme:users:group
 */
final class EnumId
{
    /**
     * Regular expression pattern for matching a valid SchemaId string.
     *
     * @constant string
     */
    const VALID_PATTERN = '/^([a-z0-9-]+):([a-z0-9\.-]+):([a-z0-9-]+)$/';

    /** @var array */
    private static $instances = [];

    /** @var string */
    private $id;

    /** @var string */
    private $vendor;

    /** @var string */
    private $package;

    /** @var string */
    private $name;

    /**
     * @param string $vendor
     * @param string $package
     * @param string $name
     */
    private function __construct($vendor, $package, $name)
    {
        $this->vendor = $vendor;
        $this->package = $package;
        $this->name = $name;
        $this->id = sprintf(
            '%s:%s:%s',
            $this->vendor,
            $this->package,
            $this->name
        );
    }

    /**
     * @param string $enumId
     *
     * @return EnumId
     *
     * @throws InvalidEnumId
     */
    public static function fromString($enumId)
    {
        if (isset(self::$instances[$enumId])) {
            return self::$instances[$enumId];
        }

        if (strlen($enumId) > 150) {
            throw new InvalidEnumId(
                sprintf('Enum id [%s] cannot be greater than 150 chars.', $enumId)
            );
        }

        if (!preg_match(self::VALID_PATTERN, $enumId, $matches)) {
            throw new InvalidEnumId(
                sprintf('Enum id [%s] is invalid. It must match the pattern [%s].', $enumId, self::VALID_PATTERN)
            );
        }

        self::$instances[$enumId] = new self($matches[1], $matches[2], $matches[3]);

        return self::$instances[$enumId];
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return string
     */
    public function getVendor()
    {
        return $this->vendor;
    }

    /**
     * @return string
     */
    public function getPackage()
    {
        return $this->package;
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
    public function getNamespace()
    {
        return sprintf(
            '%s:%s',
            $this->vendor,
            $this->package
        );
    }
}
