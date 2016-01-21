<?php

namespace Gdbots\Pbjc;

class SchemaStore
{
    /**
     * Regular expression pattern for matching a valid SchemaId string.
     *
     * Schema Id Format:
     *  pbj:vendor:package:category:message:version
     *
     * Formats:
     *  VENDOR:   [a-z0-9-]+
     *  PACKAGE:  [a-z0-9\.-]+
     *  CATEGORY: ([a-z0-9-]+)? (clarifies the intent of the message, e.g. command, request, event, response, etc.)
     *  MESSAGE:  [a-z0-9-]+
     *  VERSION:  [0-9]+-[0-9]+-[0-9])
     *
     * Examples of fully qualified schema ids:
     *  pbj:acme:videos:event:video-uploaded:1-0-0
     *  pbj:acme:users:command:register-user:1-1-0
     *  pbj:acme:api.videos:request:get-video:1-0-0
     *
     * @constant string
     */
    const VALID_PATTERN = '/^pbj:([a-z0-9-]+):([a-z0-9\.-]+):([a-z0-9-]+)?:([a-z0-9-]+):([0-9]+-[0-9]+-[0-9]+)$/';

    /** @var array */
    protected static $dirs = [];

    /** @var array */
    protected static $schemas = [];

    /**
     * Adds a directory where schemas exist.
     *
     * @param string $dir
     */
    public static function addDir($dir)
    {
        self::$dirs[$dir] = true;
    }

    /**
     * Adds an array of schema directories.
     *
     * @param array $dirs
     */
    public static function addDirs(array $dirs)
    {
        foreach ($dirs as $dir) {
            self::addDir($dir);
        }
    }

    /**
     * Returns an array of directories where schemas should exist.
     *
     * @return array
     */
    public static function getDirs()
    {
        return array_keys(self::$dirs);
    }

    /**
     * Adds a schema. An exception will be thorwn when attempting to load
     * the same id multi times.
     *
     * @param string $id
     * @param mixed  $schema
     * @param bool   $ignoreDuplication
     *
     * @throw \RuntimeException on duplicate schema id's
     */
    public static function addSchema($id, $schema, $ignoreDuplication = false)
    {
        if (isset(self::$schemas[$id]) && !$ignoreDuplication) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is already exists.', $id));
        }

        if (!self::validateSchemaId($id) && !$ignoreDuplication) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $id));
        }

        self::$schemas[$id] = $schema;
    }

    /**
     * Returns an array of schemas.
     *
     * @return array
     */
    public static function getSchemas()
    {
        return self::$schemas;
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
    public static function getSchemaById($id)
    {
        if (isset(self::$schemas[$id])) {
            return self::$schemas[$id];
        }

        throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $id));
    }

    /**
     * Validate the schema id.
     *
     * @param string $id
     *
     * @return bool
     */
    protected static function validateSchemaId($id)
    {
        return preg_match(self::VALID_PATTERN, $id, $matches) !== false;
    }
}
