<?php

namespace Gdbots\Pbjc;

class SchemaStore
{
    /** @var array */
    protected static $dirs = [];

    /** @var array */
    protected static $schemas = [];

    /** @var array */
    protected static $schemasByCurie = [];

    /** @var array */
    protected static $schemasByCurieMajor = [];

    /**
     * Adds a directory where schemas exist.
     *
     * @param string $dir
     */
    public static function addDir($dir)
    {
        self::$dirs[] = $dir;
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
        return self::$dirs;
    }

    /**
     * Adds a schema. An exception will be thorwn when attempting to load
     * the same id multi times.
     *
     * @param SchemaId         $schemaId
     * @param SchemaDescriptor $schema
     * @param bool             $ignoreDuplication
     *
     * @throw \RuntimeException on duplicate id
     */
    public static function addSchema(SchemaId $schemaId, SchemaDescriptor $schema, $ignoreDuplication = false)
    {
        if (isset(self::$schemas[$schemaId->toString()]) && !$ignoreDuplication) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is already exists.', $schemaId->toString()));
        }

        $curie = $schemaId->getCurie();
        $curieMajor = $schemaId->getCurieWithMajorRev();

        // by id
        self::$schemas[$schemaId->toString()] = $schema;

        // by curie
        if (isset(self::$schemasByCurie[$curie])) {
            $tmpSchema = self::$schemasByCurie[$curie];

            if ($schemaId->getVersion()->compare($tmpSchema->getId()->getVersion()) === 1) {
                self::$schemasByCurie[$curie] = &self::$schemas[$schemaId->toString()];
            }
        } else {
            self::$schemasByCurie[$curie] = &self::$schemas[$schemaId->toString()];
        }

        // by curie major
        if (isset(self::$schemasByCurieMajor[$curieMajor])) {
            $tmpSchema = self::$schemasByCurieMajor[$curieMajor];

            if ($schemaId->getVersion()->compare($tmpSchema->getId()->getVersion()) === 1) {
                self::$schemasByCurieMajor[$curieMajor] = &self::$schemas[$schemaId->toString()];
            }
        } else {
            self::$schemasByCurieMajor[$curieMajor] = &self::$schemas[$schemaId->toString()];
        }

        ksort(self::$schemas);
        ksort(self::$schemasByCurie);
        ksort(self::$schemasByCurieMajor);
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
     * Returns an array of schemas by curie.
     *
     * @return array
     */
    public static function getSchemasByCurie()
    {
        return self::$schemasByCurie;
    }

    /**
     * Returns an array of schemas by curie major.
     *
     * @return array
     */
    public static function getSchemasByCurieMajor()
    {
        return self::$schemasByCurieMajor;
    }

    /**
     * Returns a schema by its id. This is NOT the SchemaDescriptor object.
     * It contains more info (from the xml) about how to build this schema
     * into multiple languages. the pbj-php is specifically for php
     * (assuming it's already been compiled).
     *
     * @param SchemaId|string $schemaId
     * @param bool            $ignoreNotFound
     *
     * @return array|SchemaDescriptor|null
     */
    public static function getSchemaById($schemaId, $ignoreNotFound = false)
    {
        if ($schemaId instanceof SchemaId) {
            $schemaId = $schemaId->toString();
        }

        if (isset(self::$schemasByCurie[$schemaId])) {
            return self::$schemasByCurie[$schemaId];
        }

        if (isset(self::$schemasByCurieMajor[$schemaId])) {
            return self::$schemasByCurieMajor[$schemaId];
        }

        if (isset(self::$schemas[$schemaId])) {
            return self::$schemas[$schemaId];
        }

        if (!$ignoreNotFound) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $schemaId));
        }

        return;
    }

    /**
     * Returns the previous version of schema by id.
     *
     * @param SchemaId $schemaId
     *
     * @return SchemaDescriptor|null
     */
    public static function getPreviousSchema(SchemaId $schemaId)
    {
        $id = $schemaId->toString();

        if (isset(self::$schemas[$id])) {
            $ids = array_keys(self::$schemas);

            if (0 < $key = array_search($id, $ids)) {
                --$key;
            }

            if ($ids[$key] !== $id
              && ($prev = self::$schemas[$ids[$key]])
              && $prev->getId()->getCurieWithMajorRev() === $schemaId->getCurieWithMajorRev()
              && $prev->getId()->getVersion()->compare($schemaId->getVersion()) === -1
            ) {
                return $prev;
            }
        }

        return;
    }

    /**
     * Returns the previous major version of schema by id.
     *
     * @param SchemaId $schemaId
     *
     * @return bool
     */
    public static function hasOtherSchemaMajorRev(SchemaId $schemaId)
    {
        $curieMajor = $schemaId->getCurieWithMajorRev();

        if (isset(self::$schemasByCurieMajor[$curieMajor])) {
            $found = array_keys(self::$schemasByCurieMajor);

            return count($found) > 1;
        }

        return false;
    }
}
