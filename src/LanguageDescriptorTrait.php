<?php

namespace Gdbots\Pbjc;

trait LanguageDescriptorTrait
{
    /** @var array */
    private $languages = [];

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
}
