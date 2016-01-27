<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\ToArray;
use Gdbots\Common\Util\StringUtils;

final class Schema implements ToArray, \JsonSerializable
{
    /** @var SchemaId */
    private $id;

    /** @var Field[] */
    private $fields = [];

    /** @var array */
    private $mixins = [];

    /** @var array */
    private $languages = [];

    /** @var array */
    private $options = [];

    /** @var bool */
    private $isMixin = false;

    /** @var bool */
    private $isLatestVersion = false;

    /**
     * @param SchemaId|string $id
     * @param array           $fields
     * @param array           $mixins
     * @param array           $languages
     * @param array           $options
     */
    public function __construct($id, array $fields = [], array $mixins = [], array $languages = [], array $options = [])
    {
        $this->id = $id instanceof SchemaId ? $id : SchemaId::fromString($id);

        $this->mixins    = $mixins;
        $this->languages = $languages;
        $this->options   = $options;

        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id->toString();
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'id'         => $this->id,
            'class_name' => $this->getClassName(),
            'fields'     => $this->fields,
            'mixins'     => $this->mixins,
            'languages'  => $this->languages,
            'options'    => $this->options
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
     * @return SchemaId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the qualified (base) class name. This should be used later to
     * generate a PHP class name.
     *
     * e.g. "Message"
     *
     * @return string
     */
    public function getClassName()
    {
        return StringUtils::toCamelFromSlug($this->id->getMessage());
    }

    /**
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * @param Field $field
     */
    private function addField(Field $field)
    {
        if (!$this->hasField($field->getName())) {
            $this->fields[$field->getName()] = $field;
        }
    }

    /**
     * @param string $name
     *
     * @return Field|null
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        return null;
    }

    /**
     * @return Field[]
     */
    public function getFields()
    {
        return $this->fields ?: $this->fields = [];
    }

    /**
     * @return array
     */
    public function getMixins()
    {
        return $this->mixins ?: $this->mixins = [];
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
     * @return array
     */
    public function getOptions()
    {
        return $this->options ?: $this->options = [];
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
     * @param bool $bool
     *
     * @return this
     */
    public function setIsLatestVersion($bool)
    {
        $this->isLatestVersion = (bool) $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLatestVersion()
    {
        return $this->isLatestVersion;
    }

    /**
     * @param bool $bool
     *
     * @return this
     */
    public function setIsMixin($bool)
    {
        $this->isMixin = (bool) $bool;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMixin()
    {
        return $this->isMixin;
    }
}
