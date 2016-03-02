<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\NumberUtils;
use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Enum\FieldRule;
use Gdbots\Pbjc\Enum\Format;
use Gdbots\Pbjc\Type\StringType;
use Gdbots\Pbjc\Util\LanguageBag;

final class FieldDescriptor
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

    /** @var \Gdbots\Pbjc\Type\Type */
    private $type;

    /** @var FieldRule */
    private $rule;

    /** @var bool */
    private $required = false;

    /**
     * A regular expression to match against for string types.
     *
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

    /** @var EnumDescriptor */
    private $enum;

    /** @var LanguageBag */
    private $languages;

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
                    case 'name':
                    case 'languages':
                        continue 2;

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
                }

                $this->$classProperty = $value;
            }

            // language options
            elseif (substr($key, -8) == '_options') {
                $language = substr($key, 0, -8); // remove "_options"

                if (is_array($value)) {
                    $value = new LanguageBag($value);
                }

                $this->getLanguages()->set($language, $value);
            }
        }

        $this->name = $name;

        $this->applyDefaults();
        $this->applyFieldRule();
        $this->applyStringOptions();
        $this->applyNumericOptions();
    }

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
     * @return \Gdbots\Pbjc\Type\Type
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
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * @return Format|null
     */
    public function getFormat()
    {
        if ($this->format === Format::UNKNOWN()) {
            return;
        }

        return $this->format;
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
     * @return EnumDescriptor
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * @return LanguageBag
     */
    public function getLanguages()
    {
        return $this->languages ?: $this->languages = new LanguageBag();
    }

    /**
     * @param string $language
     *
     * @return LanguageBag
     */
    public function getLanguage($language)
    {
        if (!$this->getLanguages()->has($language)) {
            $this->getLanguages()->set($language, new LanguageBag());
        }

        return $this->getLanguages()->get($language);
    }
}
