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

        if (!$this->validateSchemaId($id)) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $id));
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

    /**
     * Validate the schema id.
     *
     * Format:
     *  vendor/package/category/message/SCHEMAVERSION.yml
     *
     * Example:
     *  gdbots/pbjx/event/event-execution-failed/1-0-0.yml
     *
     * ([\w\d_-]*)                               => vendor = gdbots
     *  \/
     * ([\w\d_-]*)                               => package = pbjx
     *  \/
     * ([\w\d_-]*)                               => category = event
     *  \/
     * ([\w\d_-]*)                               => message = event-execution-failed
     *  \/
     * (v?(\d{1,3})(\-\d+)?(\-\d+)?(\-\d+)?)(.*) => version = 1-0-0
     *
     * Note: use classical versioning
     *
     * @param string $id
     *
     * @return bool
     */
    protected function validateSchemaId($id)
    {
        if (preg_match('/([\w\d_-]*)\/([\w\d_-]*)\/([\w\d_-]*)\/([\w\d_-]*)\/(v?(\d{1,3})(\-\d+)?(\-\d+)?(\-\d+)?).yml(.*)/i', $id, $matches) !== false) {
            return !isset($matches[10]) || !$matches[10];
        }

        return false;
    }
}
