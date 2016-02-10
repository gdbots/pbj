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
     * @param bool   $isDependent
     */
    public static function addDir($dir, $isDependent = false)
    {
        self::$dirs[$dir] = $isDependent;
    }

    /**
     * Checks whether the directory is marked as a dependent.
     *
     * @param string $dir
     *
     * @return bool
     */
    public static function isDirDependent($dir)
    {
        if (isset(self::$dirs[$dir])) {
            return self::$dirs[$dir];
        }

        return false;
    }

    /**
     * Adds an array of schema directories.
     *
     * @param array $dirs
     */
    public static function addDirs(array $dirs)
    {
        foreach ($dirs as $dir => $isDependent) {
            self::addDir($dir, $isDependent);
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
     * @param string                 $id
     * @param array|SchemaDescriptor $schema
     * @param bool                   $ignoreDuplication
     *
     * @throws InvalidSchemaId on invalid id
     * @throw \RuntimeException on duplicate id
     */
    public static function addSchema($id, $schema, $ignoreDuplication = false)
    {
        if (isset(self::$schemas[$id]) && !$ignoreDuplication) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is already exists.', $id));
        }

        $schemaId = SchemaId::fromString($id);
        $curie = $schemaId->getCurie();
        $curieMajor = $schemaId->getCurieWithMajorRev();

        // by id
        self::$schemas[$id] = $schema;

        // by curie
        if (isset(self::$schemasByCurie[$curie])) {
            $tmpSchema = self::$schemasByCurie[$curie];

            $tmpId = is_array($tmpSchema)
                ? $tmpSchema['id']
                : $tmpSchema->getId()->__toString()
            ;

            $tmpSchemaId = SchemaId::fromString($tmpId);

            if ($schemaId->getVersion()->compare($tmpSchemaId->getVersion()) === 1) {
                self::$schemasByCurie[$curie] = &self::$schemas[$id];
            }
        } else {
            self::$schemasByCurie[$curie] = &self::$schemas[$id];
        }

        // by curie major
        if (isset(self::$schemasByCurieMajor[$curieMajor])) {
            $tmpSchema = self::$schemasByCurieMajor[$curieMajor];

            $tmpId = is_array($tmpSchema)
                ? $tmpSchema['id']
                : $tmpSchema->getId()->__toString()
            ;

            $tmpSchemaId = SchemaId::fromString($tmpId);

            if ($schemaId->getVersion()->compare($tmpSchemaId->getVersion()) === 1) {
                self::$schemasByCurieMajor[$curieMajor] = &self::$schemas[$id];
            }
        } else {
            self::$schemasByCurieMajor[$curieMajor] = &self::$schemas[$id];
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
     * @param string $id
     * @param bool   $ignoreNotFound
     *
     * @return array|SchemaDescriptor|null
     */
    public static function getSchemaById($id, $ignoreNotFound = false)
    {
        if (isset(self::$schemasByCurie[$id])) {
            return self::$schemasByCurie[$id];
        }

        if (isset(self::$schemasByCurieMajor[$id])) {
            return self::$schemasByCurieMajor[$id];
        }

        if (isset(self::$schemas[$id])) {
            return self::$schemas[$id];
        }

        if (!$ignoreNotFound) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $id));
        }

        return;
    }

    /**
     * Returns the previous version of schema by id.
     *
     * @param SchemaId $schemaId
     *
     * @return array|SchemaDescriptor|null
     */
    public static function getPreviousSchema(SchemaId $schemaId)
    {
        $id = $schemaId->__toString();

        if (isset(self::$schemas[$id])) {
            $ids = array_keys(self::$schemas);

            if (0 < $key = array_search($id, $ids)) {
                --$key;
            }

            if ($ids[$key] === $id) {
                return;
            }

            if (!$prev = self::$schemas[$ids[$key]]) {
                return;
            }

            // ignore lowest version
            if (is_array($prev)) {
                return;
            }

            if ($prev->getId()->getCurieWithMajorRev() === $schemaId->getCurieWithMajorRev()
                && $prev->getId()->getVersion()->compare($schemaId->getVersion()) === -1
            ) {
                return $prev;
            }
        }

        return;
    }
}
