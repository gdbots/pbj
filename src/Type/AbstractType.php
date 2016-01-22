<?php

namespace Gdbots\Pbjc\Type;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Enum\TypeName;

abstract class AbstractType implements Type
{
    private static $instances = [];

    /** @var TypeName */
    private $typeName;

    /**
     * Private constructor to ensure flyweight construction.
     *
     * @param TypeName $typeName
     */
    final private function __construct(TypeName $typeName)
    {
        $this->typeName = $typeName;
    }

    /**
     * @return static
     */
    final public static function create()
    {
        $type = get_called_class();
        if (!isset(self::$instances[$type])) {
            $a = explode('\\', $type);
            $typeName = StringUtils::toSlugFromCamel(str_replace('Type', '', end($a)));
            self::$instances[$type] = new static(TypeName::create($typeName));
        }
        return self::$instances[$type];
    }

    /**
     * {@inheritdoc}
     */
    final public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * {@inheritdoc}
     */
    final public function getTypeValue()
    {
        return $this->typeName->getValue();
    }

    /**
     * {@inheritdoc}
     */
    public function isScalar()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isBoolean()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isBinary()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isNumeric()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isString()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isMessage()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMin()
    {
        return -2147483648;
    }

    /**
     * {@inheritdoc}
     */
    public function getMax()
    {
        return 2147483647;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxBytes()
    {
        return 65535;
    }

    /**
     * {@inheritdoc}
     */
    public function allowedInSet()
    {
        return true;
    }
}
