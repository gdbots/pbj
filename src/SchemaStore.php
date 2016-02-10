<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Descriptor\SchemaDescriptor;

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
    const VALID_PATTERN = '/^pbj:([a-z0-9-]+):([a-z0-9\.-]+):([a-z0-9-]+)?:([a-z0-9-]+):([0-9]+)-([0-9]+)-([0-9]+)$/';

    /**
     * e.g. "vendor:package:category:message".
     *
     * @constant string
     */
    const VALID_CURIE_PATTERN = '/^([a-z0-9-]+):([a-z0-9\.-]+):([a-z0-9-]+)?:([a-z0-9-]+)$/';

    /**
     * e.g. "vendor:package:category:message:v1".
     *
     * @constant string
     */
    const VALID_MAJOR_VERSION_PATTERN = '/^([a-z0-9-]+):([a-z0-9\.-]+):([a-z0-9-]+)?:([a-z0-9-]+):v([0-9]+)$/';

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
     * @throw \RuntimeException on duplicate schema id's
     */
    public static function addSchema($id, $schema, $ignoreDuplication = false)
    {
        if (!$schemaElements = self::parseSchemaId($id)) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $id));
        }

        if (isset(self::$schemas[$id]) && !$ignoreDuplication) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is already exists.', $id));
        }

        $curie = sprintf(
            '%s:%s:%s:%s',
            $schemaElements['vendor'],
            $schemaElements['package'],
            $schemaElements['category'],
            $schemaElements['message']
        );

        $curieMajor = sprintf(
            '%s:v%d',
            $curie,
            $schemaElements['version']['major']
        );

        $version = sprintf(
            '%s-%s-%s',
            $schemaElements['version']['major'],
            $schemaElements['version']['minor'],
            $schemaElements['version']['patch']
        );

        // by id
        self::$schemas[$id] = $schema;

        // by curie
        if (isset(self::$schemasByCurie[$curie])) {
            $tmpSchema = self::$schemasByCurie[$curie];

            $tmpId = is_array($tmpSchema)
                ? $tmpSchema['id']
                : $tmpSchema->getId()->__toString()
            ;

            if (!$schemaElements = self::parseSchemaId($tmpId)) {
                throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $tmpId));
            }

            $tmpVersion = sprintf(
                '%s-%s-%s',
                $schemaElements['version']['major'],
                $schemaElements['version']['minor'],
                $schemaElements['version']['patch']
            );

            if (version_compare($version, $tmpVersion) === 1) {
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

            if (!$schemaElements = self::parseSchemaId($tmpId)) {
                throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $tmpId));
            }

            $tmpVersion = sprintf(
                '%s-%s-%s',
                $schemaElements['version']['major'],
                $schemaElements['version']['minor'],
                $schemaElements['version']['patch']
            );

            if (version_compare($version, $tmpVersion) === 1) {
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
        if (!$schemaElements = self::parseSchemaId($id)) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $id));
        }

        $curie = sprintf(
            '%s:%s:%s:%s',
            $schemaElements['vendor'],
            $schemaElements['package'],
            $schemaElements['category'],
            $schemaElements['message']
        );

        $curieMajor = sprintf(
            '%s:v%d',
            $curie,
            $schemaElements['version']['major']
        );

        $version = sprintf(
            '%s-%s-%s',
            $schemaElements['version']['major'],
            $schemaElements['version']['minor'],
            $schemaElements['version']['patch']
        );

        if ($version == '--') {
            if (isset(self::$schemasByCurie[$curie])) {
                return self::$schemasByCurie[$curie];
            }
        }

        if (preg_match('/^([0-9]+)--/', $version)) {
            if (isset(self::$schemasByCurieMajor[$curieMajor])) {
                return self::$schemasByCurieMajor[$curieMajor];
            }
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

            $prev = self::$schemas[$ids[$key]];

            if ($prev->getId()->getCurieWithMajorRev() === $schemaId->getCurieWithMajorRev()
                && $prev->getId()->getVersion()->compare($schemaId->getVersion()) === -1
            ) {
                return $prev;
            }
        }

        return;
    }

    /**
     * Parse the schema id.
     *
     * @param string $id
     *
     * @return array|null
     */
    protected static function parseSchemaId($id)
    {
        if (preg_match(self::VALID_PATTERN, $id, $matches)) {
            return [
                'vendor' => $matches[1],
                'package' => $matches[2],
                'category' => $matches[3],
                'message' => $matches[4],
                'version' => [
                    'major' => $matches[5],
                    'minor' => $matches[6],
                    'patch' => $matches[7],
                ],
            ];
        }

        if (preg_match(self::VALID_MAJOR_VERSION_PATTERN, $id, $matches)) {
            return [
                'vendor' => $matches[1],
                'package' => $matches[2],
                'category' => $matches[3],
                'message' => $matches[4],
                'version' => [
                    'major' => $matches[5],
                    'minor' => null,
                    'patch' => null,
                ],
            ];
        }

        if (preg_match(self::VALID_CURIE_PATTERN, $id, $matches)) {
            return [
                'vendor' => $matches[1],
                'package' => $matches[2],
                'category' => $matches[3],
                'message' => $matches[4],
                'version' => [
                    'major' => null,
                    'minor' => null,
                    'patch' => null,
                ],
            ];
        }

        return;
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
        return preg_match(self::VALID_PATTERN, $id) !== false;
    }
}
