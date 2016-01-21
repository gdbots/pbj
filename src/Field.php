<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\ToArray;
use Gdbots\Common\Util\NumberUtils;
use Gdbots\Common\Util\StringUtils;

final class Field implements ToArray, \JsonSerializable
{
    /** @var string */
    private $name;

    /** @var string */
    private $type;

    /** @var int */
    private $rule;

    /** @var bool */
    private $required = false;

    /** @var int */
    private $minLength;

    /** @var int */
    private $maxLength;

    /**
     * A regular expression to match against for string types.
     * @link http://spacetelescope.github.io/understanding-json-schema/reference/string.html#pattern
     *
     * @var string
     */
    private $pattern;

    /**
     * @link http://spacetelescope.github.io/understanding-json-schema/reference/string.html#format
     *
     * @var string
     */
    private $format;

    /** @var int */
    private $min;

    /** @var int */
    private $max;

    /** @var int */
    private $precision = 10;

    /** @var int */
    private $scale = 2;

    /** @var mixed */
    private $default;

    /** @var string */
    private $className;

    /** @var array */
    private $anyOfClassNames;

    /** @var \Closure */
    private $assertion;

    /** @var bool */
    private $overridable = false;

    /**
     * @param string        $name
     * @param string        $type
     * @param int           $rule
     * @param bool          $required
     * @param null|int      $minLength
     * @param null|int      $maxLength
     * @param null|string   $pattern
     * @param null|string   $format
     * @param null|int      $min
     * @param null|int      $max
     * @param int           $precision
     * @param int           $scale
     * @param null|mixed    $default
     * @param null|string   $className
     * @param null|array    $anyOfClassNames
     * @param \Closure|null $assertion
     * @param bool          $overridable
     */
    public function __construct(
        $name,
        $type,
        $rule                   = null,
        $required               = false,
        $minLength              = null,
        $maxLength              = null,
        $pattern                = null,
        $format                 = null,
        $min                    = null,
        $max                    = null,
        $precision              = 10,
        $scale                  = 2,
        $default                = null,
        $className              = null,
        array $anyOfClassNames  = null,
        \Closure $assertion     = null,
        $overridable            = false
    ) {
        $this->name             = $name;
        $this->type             = $type;
        $this->rule             = $rule;
        $this->required         = $required;
        $this->default          = $default;
        $this->className        = $className;
        $this->anyOfClassNames  = $anyOfClassNames;
        $this->assertion        = $assertion;
        $this->overridable      = $overridable;

        $this->applyStringOptions($minLength, $maxLength, $pattern, $format);
        $this->applyNumericOptions($min, $max, $precision, $scale);
    }

    /**
     * @param null|int $minLength
     * @param null|int $maxLength
     * @param null|string $pattern
     * @param null|string $format
     */
    private function applyStringOptions($minLength = null, $maxLength = null, $pattern = null, $format = null)
    {
        $minLength = (int) $minLength;
        $maxLength = (int) $maxLength;
        if ($maxLength > 0) {
            $this->maxLength = $maxLength;
            $this->minLength = NumberUtils::bound($minLength, 0, $this->maxLength);
        } else {
            // arbitrary string minimum range
            $this->minLength = NumberUtils::bound($minLength, 0);
        }

        $this->pattern = $pattern;
        $this->format = $format;
    }

    /**
     * @param null|int $min
     * @param null|int $max
     * @param int $precision
     * @param int $scale
     */
    private function applyNumericOptions($min = null, $max = null, $precision = 10, $scale = 2)
    {
        if (null !== $max) {
            $this->max = (int) $max;
        }

        if (null !== $min) {
            $this->min = (int) $min;
            if (null !== $this->max) {
                if ($this->min > $this->max) {
                    $this->min = $this->max;
                }
            }
        }

        $this->precision = NumberUtils::bound((int) $precision, 1, 65);
        $this->scale = NumberUtils::bound((int) $scale, 0, $this->precision);
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
     * @return string
     */
    public function getCamelizedType()
    {
        return StringUtils::toCamelFromSlug($this->type);
    }

    /**
     * @return int
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * @return int
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return Format
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return int
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @return int
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @return int
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return bool
     */
    public function hasClassName()
    {
        return null !== $this->className;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return bool
     */
    public function hasAnyOfClassNames()
    {
        return null !== $this->anyOfClassNames;
    }

    /**
     * @return array
     */
    public function getAnyOfClassNames()
    {
        return $this->anyOfClassNames;
    }

    /**
     * @return bool
     */
    public function isOverridable()
    {
        return $this->overridable;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'name'               => $this->name,
            'type'               => $this->type->getTypeValue(),
            'rule'               => $this->rule->getName(),
            'required'           => $this->required,
            'min_length'         => $this->minLength,
            'max_length'         => $this->maxLength,
            'pattern'            => $this->pattern,
            'format'             => $this->format->getValue(),
            'min'                => $this->min,
            'max'                => $this->max,
            'precision'          => $this->precision,
            'scale'              => $this->scale,
            'default'            => $this->getDefault(),
            'class_name'         => $this->className,
            'any_of_class_names' => $this->anyOfClassNames,
            'has_assertion'      => null !== $this->assertion,
            'overridable'        => $this->overridable,
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
     * Returns true if this field is likely compatible with the
     * provided field during a mergeFrom operation.
     *
     * todo: implement/test isCompatibleForMerge
     *
     * @param Field $other
     *
     * @return bool
     */
    public function isCompatibleForMerge(Field $other)
    {
        if ($this->name !== $other->name) {
            return false;
        }

        if ($this->type !== $other->type) {
            return false;
        }

        if ($this->rule !== $other->rule) {
            return false;
        }

        if ($this->className !== $other->className) {
            return false;
        }

        if (!array_intersect($this->anyOfClassNames, $other->anyOfClassNames)) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the provided field can be used as an
     * override to this field.
     *
     * @param Field $other
     * @return bool
     */
    public function isCompatibleForOverride(Field $other)
    {
        if (!$this->overridable) {
            return false;
        }

        if ($this->name !== $other->name) {
            return false;
        }

        if ($this->type !== $other->type) {
            return false;
        }

        if ($this->rule !== $other->rule) {
            return false;
        }

        if ($this->required !== $other->required) {
            return false;
        }

        return true;
    }
}
