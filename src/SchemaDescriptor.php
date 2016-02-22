<?php

namespace Gdbots\Pbjc;

final class SchemaDescriptor
{
    use LanguageDescriptorTrait;

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
