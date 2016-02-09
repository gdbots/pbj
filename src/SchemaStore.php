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
     * e.g. "vendor:package:category:message"
     *
     * @constant string
     */
    const VALID_CURIE_PATTERN = '/^([a-z0-9-]+):([a-z0-9\.-]+):([a-z0-9-]+)?:([a-z0-9-]+)$/';

    /**
     * e.g. "vendor:package:category:message:v1"
     *
     * @constant string
     */
    const VALID_MAJOR_VERSION_PATTERN = '/^([a-z0-9-]+):([a-z0-9\.-]+):([a-z0-9-]+)?:([a-z0-9-]+):v([0-9]+)$/';

    /** @var array */
    protected static $dirs = [];

    /** @var array */
    protected static $schemas = [];

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

        $curie = sprintf(
            '%s:%s:%s:%s',
            $schemaElements['vendor'],
            $schemaElements['package'],
            $schemaElements['category'],
            $schemaElements['message']
        );

        $version = sprintf(
            '%s-%s-%s',
            $schemaElements['version']['major'],
            $schemaElements['version']['minor'],
            $schemaElements['version']['patch']
        );

        if (isset(self::$schemas[$curie][$schemaElements['version']['major']][$version]) && !$ignoreDuplication) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is already exists.', $id));
        }

        self::$schemas[$curie][$schemaElements['version']['major']][$version] = $schema;

        ksort(self::$schemas[$curie][$schemaElements['version']['major']]);
        ksort(self::$schemas[$curie]);
        ksort(self::$schemas);
    }

    /**
     * Returns an array of schemas.
     *
     * @return array
     */
    public static function getSchemas()
    {
        $schemas = [];

        foreach (self::$schemas as $curie => $majors) {
            foreach ($majors as $major => $versions) {
                foreach ($versions as $schema) {
                    $id = is_array($schema)
                        ? $schema['id']
                        : $schema->getId()->__toString();

                    $schemas[$id] = $schema;
                }
            }
        }

        ksort($schemas);

        return $schemas;
    }

    /**
     * Returns a schema by its id. This is NOT the SchemaDescriptor object.
     * It contains more info (from the xml) about how to build this schema
     * into multiple languages. the pbj-php is specifically for php
     * (assuming it's already been compiled).
     *
     * @param string $id
     * @param SchemaId $schemaId
     * @param bool     $ignoreNotFound
     *
     * @return array|SchemaDescriptor|null
     */
    public static function getSchemaById($id, SchemaId $schemaId = null, $ignoreNotFound = false)
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

        $version = sprintf(
            '%s-%s-%s',
            $schemaElements['version']['major'],
            $schemaElements['version']['minor'],
            $schemaElements['version']['patch']
        );

        if ($version == '--') {
            if (isset(self::$schemas[$curie])) {
                $lastestMajor = end(self::$schemas[$curie]);
                $lastestVersion = end($lastestMajor);

                if ($schemaId) {
                    $lastestVersion = null;

                    foreach ($lastestMajor as $version => $schema) {
                        if (is_array($schema)) {
                            $sid = SchemaId::fromString($schema['id']);
                        }
                        if ($schema instanceof SchemaDescriptor) {
                            $sid = $schema->getId();
                        }
                        if ($sid->getVersion()->compare($schemaId->getVersion()) === -1) {
                            $lastestVersion = $schema;
                        }
                    }
                }

                return $lastestVersion;
            }
        }

        if (preg_match('/^([0-9]+)--/', $version)) {
            if (isset(self::$schemas[$curie][$schemaElements['version']['major']])) {
                $selectedMajor = self::$schemas[$curie][$schemaElements['version']['major']];
                $lastestVersion = end($selectedMajor);

                if ($schemaId) {
                    $lastestVersion = null;

                    foreach ($selectedMajor as $version => $schema) {
                        if (is_array($schema)) {
                            $sid = SchemaId::fromString($schema['id']);
                        }
                        if ($schema instanceof SchemaDescriptor) {
                            $sid = $schema->getId();
                        }
                        if ($sid->getVersion()->compare($schemaId->getVersion()) === -1) {
                            $lastestVersion = $schema;
                        }
                    }
                }

                return $lastestVersion;
            }
        }

        if (isset(self::$schemas[$curie][$schemaElements['version']['major']][$version])) {
            return self::$schemas[$curie][$schemaElements['version']['major']][$version];
        }

        if (!$ignoreNotFound) {
            throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $id));
        }

        return false;
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
                    'patch' => $matches[7]
                ]
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
                    'patch' => null
                ]
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
                    'patch' => null
                ]
            ];
        }

        return null;
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
