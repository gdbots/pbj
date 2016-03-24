<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Util\LanguageBag;

final class SchemaDescriptor
{
    /** @var SchemaId */
    private $id;

    /** @var SchemaDescriptor */
    private $extends;

    /** @var FieldDescriptor[] */
    private $fields = [];

    /** @var SchemaDescriptor[] */
    private $mixins = [];

    /** @var bool */
    private $isMixin = false;

    /** @var bool */
    private $isLatestVersion = false;

    /** @var LanguageBag */
    private $languages = [];

    /** @var bool */
    private $deprecated = false;

    /**
     * @param SchemaId|string $id
     * @param array           $parameters
     */
    public function __construct($id, array $parameters = [])
    {
        $this->id = $id instanceof SchemaId ? $id : SchemaId::fromString($id);

        foreach ($parameters as $key => $value) {
            $classProperty = lcfirst(StringUtils::toCamelFromSlug($key));

            // existing properties
            if (property_exists(get_called_class(), $classProperty)) {
                switch ($classProperty) {
                    case 'isMixin':
                    case 'isLatestVersion':
                    case 'deprecated':
                        $value = (bool) $value;
                        break;

                    case 'fields':
                        $fields = [];

                        /** @var FieldDescriptor $field */
                        foreach ($value as $field) {
                            $fields[$field->getName()] = $field;
                        }

                        $value = $fields;

                        break;

                    case 'mixins':
                        $mixins = [];

                        /** @var SchemaDescriptor $mixin */
                        foreach ($value as $mixin) {
                            $mixins[$mixin->getId()->getCurieWithMajorRev()] = $mixin;
                        }

                        $value = $mixins;

                        break;
                }

                $this->$classProperty = $value;
            }
        }
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
                $mixin->getFields(),
                $mixin->getInheritedFields()
            );
        }

        return $fields;
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
     * @return bool
     */
    public function isMixinSchema()
    {
        return $this->isMixin;
    }

    /**
     * @param bool $bool
     *
     * @return self
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

    /**
     * @return bool
     */
    public function isDeprecated()
    {
        return $this->deprecated;
    }
}
