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

    /** @var array */
    protected static $enums = [];

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
     * Adds a schema. An exception will be thrown when attempting to load
     * the same id multi times.
     *
     * @param SchemaId         $schemaId
     * @param SchemaDescriptor $schema
     *
     * @throw \RuntimeException on duplicate id
     */
    public static function addSchema(SchemaId $schemaId, SchemaDescriptor $schema)
    {
        $curie = $schemaId->getCurie();
        $curieMajor = $schemaId->getCurieWithMajorRev();

        // by id
        self::$schemas[$schemaId->toString()] = $schema;

        // by curie
        if (isset(self::$schemasByCurie[$curie])) {
            /** @var SchemaDescriptor $tmpSchema */
            $tmpSchema = self::$schemasByCurie[$curie];

            if ($schemaId->getVersion()->compare($tmpSchema->getId()->getVersion()) === 1) {
                self::$schemasByCurie[$curie] = &self::$schemas[$schemaId->toString()];
            }
        } else {
            self::$schemasByCurie[$curie] = &self::$schemas[$schemaId->toString()];
        }

        // by curie major
        if (isset(self::$schemasByCurieMajor[$curieMajor])) {
            /** @var SchemaDescriptor $tmpSchema */
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
     * Returns an array of schemas by namespaces.
     *
     * @param array $namespaces
     *
     * @return array
     */
    public static function getSchemasByNamespaces(array $namespaces)
    {
        $schemas = [];

        /** @var SchemaDescriptor $schema */
        foreach (self::$schemasByCurie as $schema) {
            if (in_array($schema->getId()->getNamespace(), $namespaces)) {
                $schemas[] = $schema;
            }
        }

        return $schemas;
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
     * @return SchemaDescriptor|null
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

            if ($ids[$key] !== $id) {
                /** @var SchemaDescriptor $prev */
                $prev = self::$schemas[$ids[$key]];

                if ($prev
                    && $prev->getId()->getCurieWithMajorRev() === $schemaId->getCurieWithMajorRev()
                    && $prev->getId()->getVersion()->compare($schemaId->getVersion()) === -1
                ) {
                    return $prev;
                }
            }
        }

        return;
    }

    /**
     * Returns all schemas with the same curie.
     *
     * @param SchemaId $schemaId
     *
     * @return array|bool
     */
    public static function getAllSchemaVersions(SchemaId $schemaId)
    {
        $schemaIds = preg_grep(sprintf('/(%s):([0-9]+)-([0-9]+)-([0-9]+)/', $schemaId->getCurie()), array_keys(self::$schemas));

        if ($schemaIds === false || count($schemaIds) === 0) {
            return false;
        }

        $schemas = [];

        foreach ($schemaIds as $schemaId) {
            $schemas[] = self::$schemas[$schemaId];
        }

        return $schemas;
    }

    /**
     * Checks if schema has additional major version.
     *
     * @param SchemaId $schemaId
     *
     * @return bool
     */
    public static function hasOtherSchemaMajorRev(SchemaId $schemaId)
    {
        if (isset(self::$schemasByCurieMajor[$schemaId->getCurieWithMajorRev()])) {
            if (preg_match_all(
                    sprintf('/(%s:v[0-9]+)/', $schemaId->getCurie()),
                    implode(' ', array_keys(self::$schemasByCurieMajor)),
                    $matches
                ) !== false
            ) {
                return count($matches[1]) > 1;
            }
        }

        return false;
    }

    /**
     * Returns list of all schemas with major version.
     *
     * @param SchemaId $schemaId
     *
     * @return array|bool
     */
    public static function getOtherSchemaMajorRev(SchemaId $schemaId)
    {
        if (isset(self::$schemasByCurieMajor[$schemaId->getCurieWithMajorRev()])) {
            if (preg_match_all(
                    sprintf('/(%s:v[0-9]+)/', $schemaId->getCurie()),
                    implode(' ', array_keys(self::$schemasByCurieMajor)),
                    $matches
                ) !== false
            ) {
                $schemas = [];

                foreach ($matches[1] as $curieMajor) {
                    $schemas[] = self::$schemasByCurieMajor[$curieMajor];
                }

                return $schemas;
            }
        }

        return false;
    }

    /**
     * Adds an enum. An exception will be thrown when attempting to load
     * the same id multi times.
     *
     * @param EnumId         $enumId
     * @param EnumDescriptor $enum
     *
     * @throw \RuntimeException on duplicate id
     */
    public static function addEnum(EnumId $enumId, EnumDescriptor $enum)
    {
        self::$enums[$enumId->toString()] = $enum;

        ksort(self::$enums);
    }

    /**
     * Returns an enum by its id.
     *
     * @param EnumId|string $enumId
     * @param bool          $ignoreNotFound
     *
     * @return EnumDescriptor|null
     */
    public static function getEnumById($enumId, $ignoreNotFound = false)
    {
        if ($enumId instanceof EnumId) {
            $enumId = $enumId->toString();
        }

        if (isset(self::$enums[$enumId])) {
            return self::$enums[$enumId];
        }

        if (!$ignoreNotFound) {
            throw new \RuntimeException(sprintf('Enum with id "%s" is invalid.', $enumId));
        }

        return;
    }

    /**
     * Returns an array of enums.
     *
     * @return array
     */
    public static function getEnums()
    {
        return self::$enums;
    }
}
