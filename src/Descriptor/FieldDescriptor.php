<?php

namespace Gdbots\Pbjc\Descriptor;

use Gdbots\Common\Util\ArrayUtils;
use Gdbots\Common\Util\NumberUtils;
use Gdbots\Common\Util\StringUtils;
use Gdbots\Identifiers\Identifier;
use Gdbots\Pbjc\Enum\FieldRule;
use Gdbots\Pbjc\Enum\Format;
use Gdbots\Pbjc\Enum\TypeName;
use Gdbots\Pbjc\Type\Type;

final class FieldDescriptor extends Descriptor
{
    /**
     * Regular expression pattern for matching a valid field name.  The pattern allows
     * for camelCase fields name but snake_case is recommend.
     *
     * @constant string
     */
    const VALID_NAME_PATTERN = '/^([a-zA-Z_]{1}[a-zA-Z0-9_]+)$/';

    /** @var string */
    private $name;

    /** @var Type */
    private $type;

    /** @var FieldRule */
    private $rule;

    /** @var bool */
    private $required = false;

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
     * @var Format
     */
    private $format;

    /** @var int */
    private $minLength;

    /** @var int */
    private $maxLength;

    /** @var int */
    private $min;

    /** @var int */
    private $max;

    /** @var int */
    private $precision;

    /** @var int */
    private $scale;

    /** @var mixed */
    private $default;

    /** @var bool */
    private $useTypeDefault = false;

    /** @var array */
    private $anyOf;

    /** @var bool */
    private $overridable = false;

    /** @var array */
    private $options = [];

    /**
     * @param string $name
     * @param array  $parameters
     *
     * @throw \InvalidArgumentException
     */
    public function __construct($name, array $parameters)
    {
        if (!$name || strlen($name) > 127 || preg_match(self::VALID_NAME_PATTERN, $name) === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Field [%s] must match pattern [%s] with less than 127 characters.',
                    $name,
                    self::VALID_NAME_PATTERN
                )
            );
        }

        foreach ($parameters as $key => $value) {
            $classProperty = lcfirst(StringUtils::toCamelFromSnake($key));

            // existing properties
            if (property_exists(get_called_class(), $classProperty)) {
                switch ($key) {
                    case 'type':
                        $class = sprintf(
                            '\\Gdbots\\Pbjc\\Type\\%sType',
                            StringUtils::toCamelFromSlug($parameters['type'])
                        );

                        $value = $class::create();
                        break;

                    case 'rule':
                        if (null !== $value && in_array($value, FieldRule::values())) {
                            $value = FieldRule::create($value);
                        }
                        break;

                    case 'format':
                        if (null !== $value && in_array($value, Format::values())) {
                            $value = Format::create($value);
                        }
                        break;

                    case 'required':
                    case 'use_type_default':
                    case 'overridable':
                        $value = (bool) $value;
                        break;

                    case 'min':
                    case 'max':
                    case 'minLength':
                    case 'maxLength':
                    case 'precision':
                    case 'scale':
                        $value = (int) $value;
                        break;

                    case 'options':
                        foreach ($value as $k => $v) {
                            $this->setOption($k, $v);
                        }

                        continue 2;
                }

                $this->$classProperty = $value;
            }

            // lanauge options
            elseif (substr($key, -8) == '_options') {
                $language = substr($key, 0, -8); // remove "_options"

                $this->setOption($language, $value);
            }

            // other
            elseif (!empty($value)) {
                $this->setOption($key, $value);
            }
        }

        $this->applyDefaults();
        $this->applyFieldRule();
        $this->applyStringOptions();
        $this->applyNumericOptions();
    }

    /**
     * @return void
     */
    private function applyDefaults()
    {
        $this->format = $this->format ?: Format::UNKNOWN();
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function applyFieldRule()
    {
        $this->rule = $this->rule ?: FieldRule::A_SINGLE_VALUE();
        if ($this->isASet() && !$this->type->allowedInSet()) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Field [%s] with type [%s] cannot be used in a set.',
                    $this->name,
                    $this->type->getTypeValue()
                )
            );
        }
    }

    /**
     * @return void
     */
    private function applyStringOptions()
    {
        // use *Length for string type
        if ($this->type instanceof StringType) {
            $this->minLength = $this->min;
            $this->maxLength = $this->max;
            $this->min = null;
            $this->max = null;
        }

        if ($this->maxLength > 0) {
            $this->minLength = NumberUtils::bound($this->minLength, 0, $this->maxLength);
        } else {
            // arbitrary string minimum range
            $this->minLength = NumberUtils::bound($this->minLength, 0, $this->type->getMaxBytes());
        }
    }

    /**
     * @return void
     */
    private function applyNumericOptions()
    {
        if (null !== $this->min) {
            if (null !== $this->max) {
                if ($this->min > $this->max) {
                    $this->min = $this->max;
                }
            }
        }

        $this->precision = NumberUtils::bound($this->precision, 0, 65); // range 1-65 (we use 0 to ignore when generating class)
        $this->scale = NumberUtils::bound($this->scale, 0, $this->precision);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return FieldRule
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * @return bool
     */
    public function isASingleValue()
    {
        //return FieldRule::A_SINGLE_VALUE === $this->rule->getValue();
        return false;
    }

    /**
     * @return bool
     */
    public function isASet()
    {
        return FieldRule::A_SET === $this->rule->getValue();
    }

    /**
     * @return bool
     */
    public function isAList()
    {
        return FieldRule::A_LIST === $this->rule->getValue();
    }

    /**
     * @return bool
     */
    public function isAMap()
    {
        return FieldRule::A_MAP === $this->rule->getValue();
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
        if ($this->format === Format::UNKNOWN()) {
            return null;
        }

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
        if (null === $this->default) {
            if ($this->useTypeDefault) {
                return $this->isASingleValue() ? $this->type->getDefault() : [];
            }
            return $this->isASingleValue() ? null : [];
        }

        return $this->default;
    }

    /**
     * @return bool
     */
    public function isUseTypeDefault()
    {
        return $this->useTypeDefault;
    }

    /**
     * @return bool
     */
    public function hasAnyOf()
    {
        return null !== $this->anyOf;
    }

    /**
     * @return array
     */
    public function getAnyOf()
    {
        return $this->anyOf;
    }

    /**
     * @return bool
     */
    public function isOverridable()
    {
        return $this->overridable;
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
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'name'             => $this->name,
            'type'             => $this->type->getTypeValue(),
            'rule'             => $this->rule->getName(),
            'required'         => $this->required,
            'pattern'          => $this->pattern,
            'format'           => $this->format->getValue(),
            'min_length'       => $this->minLength,
            'max_length'       => $this->maxLength,
            'min'              => $this->min,
            'max'              => $this->max,
            'precision'        => $this->precision,
            'scale'            => $this->scale,
            'default'          => $this->getDefault(),
            'use_type_default' => $this->useTypeDefault,
            'any_of'           => $this->anyOf,
            'overridable'      => $this->overridable,
            'options'          => $this->options
        ];
    }
}
