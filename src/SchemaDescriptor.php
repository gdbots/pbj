<?php

namespace Gdbots\Pbjc;

final class SchemaDescriptor
{
    /** @var SchemaId */
    private $id;

    /** @var SchemaDescriptor */
    private $extends;

    /** @var FieldDescriptor[] */
    private $fields = [];

    /** @var EnumDescriptor[] */
    private $enums = [];

    /** @var SchemaDescriptor[] */
    private $mixins = [];

    /** @var array */
    private $languages = [];

    /** @var bool */
    private $isMixin = false;

    /** @var bool */
    private $isLatestVersion = false;

    /**
     * @param SchemaId|string $id
     */
    public function __construct($id)
    {
        $this->id = $id instanceof SchemaId ? $id : SchemaId::fromString($id);
    }

    /**
     * @return string
     */
    public function toString()
    {
        return $this->id->toString();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @return SchemaId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return SchemaDescriptor
     */
    public function getExtends()
    {
        return $this->extends;
    }

    /**
     * @param SchemaDescriptor $extends
     *
     * @return this
     */
    public function setExtends(SchemaDescriptor $extends)
    {
        $this->extends = $extends;

        return $this;
    }

    /**
     * @param FieldDescriptor $field
     */
    public function addField(FieldDescriptor $field)
    {
        if (!isset($this->fields[$field->getName()])) {
            $this->fields[$field->getName()] = $field;
        }
    }

    /**
     * @param string $name
     *
     * @return FieldDescriptor|null
     */
    public function getField($name)
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        return;
    }

    /**
     * @return FieldDescriptor[]
     */
    public function getFields()
    {
        return $this->fields ?: $this->fields = [];
    }

    /**
     * @return FieldDescriptor[]
     */
    public function getInheritedFields()
    {
        $fields = [];

        foreach ($this->getMixins() as $mixin) {
            $fields = array_merge(
                $fields,
                $mixin->getInheritedFields(),
                $mixin->getFields()
            );
        }

        return $fields;
    }

    /**
     * @param EnumDescriptor $enum
     */
    public function addEnum(EnumDescriptor $enum)
    {
        if (!isset($this->enums[$enum->getName()])) {
            $this->enums[$enum->getName()] = $enum;
        }
    }

    /**
     * @param string $name
     *
     * @return EnumDescriptor|null
     */
    public function getEnum($name)
    {
        if (isset($this->enums[$name])) {
            return $this->enums[$name];
        }

        return;
    }

    /**
     * @return EnumDescriptor[]
     */
    public function getEnums()
    {
        return $this->enums ?: $this->enums = [];
    }

    /**
     * @param SchemaDescriptor $mixin
     */
    public function addMixin(SchemaDescriptor $mixin)
    {
        if (!isset($this->mixins[$mixin->getId()->getCurieWithMajorRev()])) {
            $this->mixins[$mixin->getId()->getCurieWithMajorRev()] = $mixin;
        }
    }

    /**
     * @param string $curieWithMajorRev
     *
     * @return SchemaDescriptor|null
     */
    public function getMixin($curieWithMajorRev)
    {
        if (isset($this->mixins[$curieWithMajorRev])) {
            return $this->mixins[$curieWithMajorRev];
        }

        return;
    }

    /**
     * @return SchemaDescriptor[]
     */
    public function getMixins()
    {
        return $this->mixins ?: $this->mixins = [];
    }

    /**
     * @param string $language
     * @param array  $options
     *
     * @return this
     */
    public function setLanguage($language, array $options)
    {
        $this->languages[$language] = $options;

        return $this;
    }

    /**
     * @param string $language
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getLanguage($language, $default = [])
    {
        if (isset($this->languages[$language])) {
            return $this->languages[$language];
        }

        return $default;
    }

    /**
     * @param string $language
     * @param string $key
     * @param mixed  $value
     *
     * @return this
     */
    public function setLanguageKey($language, $key, $value = null)
    {
        if (!isset($this->languages[$language])) {
            $this->languages[$language] = [];
        }

        $this->languages[$language][$key] = $value;

        return $this;
    }

    /**
     * @param string $language
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getLanguageKey($language, $key, $default = null)
    {
        if (isset($this->languages[$language][$key])) {
            return $this->languages[$language][$key];
        }

        return $default;
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        return $this->languages ?: $this->languages = [];
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
    public function isMixinSchema()
    {
        return $this->isMixin;
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
}
