<?php

namespace Gdbots\Pbjc;

class SchemaStore
{
    /** @var array */
    protected $dirs = [];

    /** @var array */
    protected $schemas = [];

    /**
     * Adds a directory where schemas exist.
     *
     * @param string $dir
     *
     * @return this
     */
    public function addDir($dir)
    {
        if (!in_array($dir, $this->dirs)) {
            $this->dirs[] = $dir;
        }

        return $this;
    }

    /**
     * Returns an array of directories where schemas should exist.
     *
     * @return array
     */
    public function getDirs()
    {
        return $this->dirs;
    }

    /**
     * Adds a schema. An exception will be thorwn when attempting to load
     * the same id multi times.
     *
     * @param string $id
     * @param mixed  $schema
     *
     * @return this
     *
     * @throw \RuntimeException on duplicate schema id's
     */
    public function addSchema($id, $schema)
    {
        if (array_key_exists($dir, $this->schemas)) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is already exists.', $id));
        }

        $this->schemas[$id] = $schema;
    }

    /**
     * Returns a schema by its id. This is NOT the \Gdbots\Pbj\Schema object.
     * It contains more info (from the yaml) about how to build this schema
     * into multiple languages. the pbj-php is specifically for php
     * (assuming it's already been compiled).
     *
     * @param string $id
     *
     * @return mixed|null
     */
    public function getSchemaById($id)
    {
        if (array_key_exists($id, $this->schemas)) {
            return $this->schemas[$id];
        }

        return null;
    }
}
