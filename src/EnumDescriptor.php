<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Util\ParameterBag;

final class EnumDescriptor
{
    /** @var EnumId */
    private $id;

    /** @var string */
    private $type;

    /** @var array */
    private $values = [];

    /** @var ParameterBag */
    private $languages = [];

    /**
     * @param EnumId|string $id
     * @param string        $type
     * @param array         $values
     * @param ParameterBag  $languages
     */
    public function __construct($id, $type, array $values, ParameterBag $languages = null)
    {
        $this->id = $id instanceof EnumId ? $id : EnumId::fromString($id);
        $this->type = $type;
        $this->values = $values;
        $this->languages = $languages;
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
     * @return EnumId
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values ?: $this->values = [];
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function hasValue($key)
    {
        return isset($this->values[$key]);
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
