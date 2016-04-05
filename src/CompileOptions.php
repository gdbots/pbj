<?php

namespace Gdbots\Pbjc;

final class CompileOptions
{
    /** @var array */
    private $namespaces = [];

    /** @var string|array */
    private $domain;

    /** @var string */
    private $output;

    /** @var string */
    private $manifest;

    /** @var \Closure */
    private $callback;

    /**
     * Construct.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @return array
     */
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * @return string|array
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param string $namespace
     *
     * @return string|null
     */
    public function getDomainByNamespace($namespace = null)
    {
        if (is_string($this->domain)) {
            return $this->domain;
        }

        if (isset($this->domain[$namespace])) {
            return $this->domain[$namespace];
        }

        if (isset($this->domain['default'])) {
            return $this->domain['default'];
        }

        return null;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getManifest()
    {
        return $this->manifest;
    }

    /**
     * @return \Closure
     */
    public function getCallback()
    {
        return $this->callback;
    }
}
