<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Util\ParameterBag;

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

    /** @var ParameterBag */
    private $languages = [];

    /**
     * @param SchemaId|string       $id
     * @param SchemaDescriptor|null $extends
     * @param FieldDescriptor[]     $fields
     * @param SchemaDescriptor[]    $mixins
     * @param ParameterBag          $languages
     * @param bool                  $isMixin
     * @param bool                  $isLatestVersion
     */
    public function __construct(
        $id,
        SchemaDescriptor $extends = null,
        array $fields = [],
        array $mixins = [],
        ParameterBag $languages = null,
        $isMixin = false,
        $isLatestVersion = false
    ) {
        $this->id = $id instanceof SchemaId ? $id : SchemaId::fromString($id);
        $this->extends = $extends;
        $this->languages = $languages;
        $this->isMixin = $isMixin;
        $this->isLatestVersion = $isLatestVersion;

        $this->fields = [];
        foreach ($fields as $field) {
            $this->fields[$field->getName()] = $field;
        }

        $this->mixins = [];
        foreach ($mixins as $mixin) {
            $this->mixins[$mixin->getId()->getCurieWithMajorRev()] = $mixin;
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
                $mixin->getInheritedFields(),
                $mixin->getFields()
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
     * @return ParameterBag
     */
    public function getLanguages()
    {
        return $this->languages ?: $this->languages = new ParameterBag();
    }

    /**
     * @param string $language
     *
     * @return ParameterBag
     */
    public function getLanguage($language)
    {
        if (!$this->getLanguages()->has($language)) {
            $this->getLanguages()->set($language, new ParameterBag());
        }

        return $this->getLanguages()->get($language);
    }
}
