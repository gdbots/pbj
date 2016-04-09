<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Util\LanguageBag;

final class EnumDescriptor
{
    /** @var EnumId */
    private $id;

    /** @var string */
    private $type;

    /** @var array */
    private $values = [];

    /** @var LanguageBag */
    private $languages = [];

    /** @var bool */
    private $deprecated = false;

    /**
     * @param EnumId|string $id
     * @param string        $type
     * @param array         $values
     * @param LanguageBag   $languages
     * @param bool          $deprecated
     */
    public function __construct($id, $type, array $values, LanguageBag $languages = null, $deprecated = false)
    {
        $this->id = $id instanceof EnumId ? $id : EnumId::fromString($id);
        $this->type = $type;
        $this->values = $values;
        $this->languages = $languages;
        $this->deprecated = $deprecated;
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
     * @param string $value
     *
     * @return bool
     */
    public function hasValue($value)
    {
        return in_array($value, $this->values);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function getKeyByValue($value)
    {
        return array_search($value, $this->values);
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
