<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\ToArray;
use Gdbots\Common\Util\ArrayUtils;
use Gdbots\Common\Util\NumberUtils;
use Gdbots\Common\Util\StringUtils;
use Gdbots\Identifiers\Identifier;
use Gdbots\Pbjc\Enum\FieldRule;
use Gdbots\Pbjc\Enum\Format;
use Gdbots\Pbjc\Enum\TypeName;
use Gdbots\Pbjc\Type\Type;
use Gdbots\Pbjc\Type\StringEnumType;

final class Field implements ToArray, \JsonSerializable
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
     * @var Format
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

    /** @var bool */
    private $useTypeDefault = true;

    /** @var string */
    private $className;

    /** @var array */
    private $anyOfClassNames;

    /** @var bool */
    private $overridable = false;

    /** @var array */
    private $languages = [];

    /**
     * @param string      $name
     * @param Type        $type
     * @param FieldRule   $rule
     * @param bool        $required
     * @param null|int    $minLength
     * @param null|int    $maxLength
     * @param null|string $pattern
     * @param null|string $format
     * @param null|int    $min
     * @param null|int    $max
     * @param int         $precision
     * @param int         $scale
     * @param null|mixed  $default
     * @param bool        $useTypeDefault
     * @param null|string $className
     * @param null|array  $anyOfClassNames
     * @param bool        $overridable
     * @param array       $languages
     *
     * @throw \InvalidArgumentException
     */
    public function __construct(
        $name,
        Type $type,
        FieldRule $rule        = null,
        $required              = false,
        $minLength             = null,
        $maxLength             = null,
        $pattern               = null,
        $format                = null,
        $min                   = null,
        $max                   = null,
        $precision             = 10,
        $scale                 = 2,
        $default               = null,
        $useTypeDefault        = true,
        $className             = null,
        array $anyOfClassNames = null,
        $overridable           = false,
        array $languages       = []
    ) {
        if (!$name || strlen($name) > 127 || preg_match(self::VALID_NAME_PATTERN, $name) === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Field [%s] must match pattern [%s] with less than 127 characters.',
                    $name,
                    self::VALID_NAME_PATTERN
                )
            );
        }

        $required       = (bool) $required;
        $useTypeDefault = (bool) $useTypeDefault;
        $overridable    = (bool) $overridable;

        $this->name            = $name;
        $this->type            = $type;
        $this->required        = $required;
        $this->default         = $default;
        $this->useTypeDefault  = $useTypeDefault;
        $this->className       = $className;
        $this->anyOfClassNames = $anyOfClassNames;
        $this->overridable     = $overridable;
        $this->languages       = $languages;

        $this->applyFieldRule($rule);
        $this->applyStringOptions($minLength, $maxLength, $pattern, $format);
        $this->applyNumericOptions($min, $max, $precision, $scale);
    }

    /**
     * Create instrance from array
     *
     * @param string $name
     * @param array  $parameters
     */
    public static function fromArray($name, array $parameters)
    {
        $typeClass = sprintf(
            '\\Gdbots\\Pbjc\\Type\\%sType',
            StringUtils::toCamelFromSlug($parameters['type'])
        );

        $args = [
            'name' => $name,
            'type' => $typeClass::create(),
            'rule' => null,
            'required' => false,
            'min_length' => null,
            'max_length' => null,
            'pattern' => null,
            'format' => null,
            'min' => null,
            'max' => null,
            'precision' => 10,
            'scale' => 2,
            'default' => null,
            'use_type_default' => true,
            'class_name' => null,
            'any_of' => null,
            'overridable' => false,
            'language_options' => []
        ];

        foreach ($parameters as $property => $value) {
            $classProperty = lcfirst(StringUtils::toCamelFromSnake($property));
            if (property_exists(get_called_class(), $classProperty) && $property != 'type') {
              $args[$property] = $value;
            } elseif ($property == 'any_of' && isset($value['id'])) {
                $args['any_of'] = (array) $value['id'];
            } else {
                $language = substr($property, 0, -8); // remove "_options"

                if (in_array($language, Compiler::LANGUAGES)) {
                    $args['language_options'][$language] = $value;
                }
            }
        }

        /**
         * Handle special types:
         */

        // use *_length for string type
        if ($args['type'] instanceof StringType) {
            $args['min_length'] = $args['min'];
            $args['max_length'] = $args['max'];
            $args['min'] = null;
            $args['max'] = null;
        }

        // generate PHP style default (using class)
        if ($args['type'] instanceof IntEnumType
            || $args['type'] instanceof StringEnumType
        ) {
            if (isset($args['default'])
                && isset($args['language_options']['php']['class_name'])
                && isset($parameters['enumerations']['enumeration'])
            ) {
                $className    = $args['language_options']['php']['class_name'];
                $enumerations = $parameters['enumerations']['enumeration'];

                /* @todo: handle not existing class */
                /* if (!class_exists($className)) {
                    throw new \InvalidArgumentException(
                        sprintf(
                            'Field [%s] Enum class [%s] does not exists.',
                            $name,
                            $className
                        )
                    );
                } */

                if (substr($className, 0, 1) == '\\') {
                    $className = substr($className, 1);
                }

                // search for key by value
                $key = null;
                foreach ($enumerations as $enumeration) {
                    if (strtolower($enumeration['value']) == strtolower($args['default'])) {
                        $key = $enumeration['key'];
                        break;
                    }
                }

                if ($key) {
                    $args['language_options']['php']['default'] = sprintf('%s::%s()', substr($className, strrpos($className, '\\')+1), strtoupper($key));
                }
            }
        }

        $class = new \ReflectionClass(get_called_class());
        return $class->newInstanceArgs(array_values($args));
    }

    /**
     * @param FieldRule $rule
     *
     * @throws \InvalidArgumentException
     */
    private function applyFieldRule(FieldRule $rule = null)
    {
        $this->rule = $rule ?: FieldRule::A_SINGLE_VALUE();
        if ($this->isASet() && $this->type->allowedInSet()) {
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
     * @param null|int    $minLength
     * @param null|int    $maxLength
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
            $this->minLength = NumberUtils::bound($minLength, 0, $this->type->getMaxBytes());
        }

        $this->pattern = $pattern;
        if (null !== $format && in_array($format, Format::values())) {
            $this->format = Format::create($format);
        } else {
            $this->format = Format::UNKNOWN();
        }
    }

    /**
     * @param null|int $min
     * @param null|int $max
     * @param int      $precision
     * @param int      $scale
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
        return FieldRule::A_SINGLE_VALUE === $this->rule->getValue();
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
        if (null === $this->maxLength) {
            return $this->type->getMaxBytes();
        }
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
        if (null === $this->min) {
            return $this->type->getMin();
        }
        return $this->min;
    }

    /**
     * @return int
     */
    public function getMax()
    {
        if (null === $this->max) {
            return $this->type->getMax();
        }
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
     * @return array
     */
    public function getLanguages()
    {
        return $this->languages ?: $this->languages = [];
    }

    /**
     * @param string $language
     * @param array  $options
     *
     * @return this
     */
    public function setLanguage($language, array $options = [])
    {
        if (!isset($this->languages[$language])) {
            $this->languages[$language] = [];
        }

        $this->languages[$language] = $options;

        return $this;
    }

    /**
     * @param string $language
     *
     * @return array
     */
    public function getLanguageOptions($language)
    {
        if (isset($this->languages[$language])) {
            return $this->languages[$language];
        }

        return [];
    }

    /**
     * @param string $language
     * @param string key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getLanguageOption($language, $key, $default = null)
    {
        if (isset($this->languages[$language][$key])) {
            return $this->languages[$language][$key];
        }

        return $default;
    }

    /**
     * @param string $language
     * @param string key
     * @param mixed  $value
     *
     * @return this
     */
    public function setLanguageOption($language, $key, $value)
    {
        if (isset($this->languages[$language])) {
            $this->languages[$language] = [];
        }

        $this->languages[$language][$key] = $value;

        return $this;
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
            'use_type_default'   => $this->useTypeDefault,
            'class_name'         => $this->className,
            'any_of_class_names' => $this->anyOfClassNames,
            'overridable'        => $this->overridable,
            'language_options'   => $this->languages
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
