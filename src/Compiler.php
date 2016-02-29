<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Exception\MissingSchema;
use Gdbots\Pbjc\Util\XmlUtils;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class Compiler
{
    /**
     * Construct.
     */
    public function __construct()
    {
        list($enums, $schemas) = $this->loadXmlFiles();

        $this->loadParseEnums($enums);
        $this->loadParseSchemas($schemas);
    }

    /**
     * Reads all xml files from all stored directories.
     *
     * @return array [enums, schemas]
     *
     * @throws \RuntimeException
     */
    private function loadXmlFiles()
    {
        $enums = [];
        $schemas = [];

        // load all schema and store XML data
        foreach (SchemaStore::getDirs() as $dir) {
            $files = Finder::create()->files()->in($dir)->name('*.xml');

            foreach ($files as $key => $file) {
                if ($file->getFilename() == 'enums.xml') {
                    $this->addEnumXml($file, $enums);
                } else {
                    $this->addSchemaXml($file, $schemas);
                }
            }
        }

        ksort($schemas);
        ksort($enums);

        return [$enums, $schemas];
    }

    /**
     * Reads and validate XML file, and add all $enums.
     *
     * @param SplFileInfo $file
     * @param array       $enums
     *
     * @throw \RuntimeException
     */
    private function addEnumXml(SplFileInfo $file, &$enums)
    {
        // invalid schema
        if (!$xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../enums.xsd')) {
            throw new \RuntimeException(sprintf(
                'Invalid enums xml file "%s".',
                $file
            ));
        }

        // bad \DOMDocument
        if (!$xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
            throw new \RuntimeException('Invalid enum DOM object.');
        }

        $namespace = $xmlData['enums']['namespace'];

        $filePath = substr($file->getPathName(), 0, -strlen($file->getFilename()) - 1);
        $enumsPath = str_replace(':', '/', $namespace);

        // invalid enum file location
        if (strrpos($filePath, $enumsPath) === false) {
            throw new \RuntimeException(sprintf(
                'Invalid enums xml file "%s" location. Expected location "%s".',
                $filePath,
                $enumsPath
            ));
        }

        // get language options
        $languages = [];
        foreach ($xmlData['enums'] as $key => $value) {
            if (substr($key, -8) == '_options') {
                $languages[$key] = $value;
            }
        }

        if (isset($xmlData['enums']['enum'])) {
            foreach ($xmlData['enums']['enum'] as $enum) {
                $enumId = EnumId::fromString(sprintf('%s:%s', $namespace, $enum['name']));

                // duplicate schema
                if (array_key_exists($enumId->toString(), $enums)) {
                    throw new \RuntimeException(sprintf(
                        'Duplicate enum "%s" in file "%s".',
                        $enumId->toString(),
                        $file
                    ));
                }

                $enums[$enumId->toString()] = array_merge($enum, $languages, ['namespace' => $namespace]);
            }
        }
    }

    /**
     * Reads and validate XML file, and add schema to $schemas.
     *
     * @param SplFileInfo $file
     * @param array       $schemas
     *
     * @throw \RuntimeException
     */
    private function addSchemaXml(SplFileInfo $file, &$schemas)
    {
        // invalid schema
        if (!$xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../schema.xsd')) {
            throw new \RuntimeException(sprintf(
                'Invalid schema xml file "%s".',
                $file
            ));
        }

        // bad \DOMDocument
        if (!$xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
            throw new \RuntimeException('Invalid schema DOM object.');
        }

        $schemaId = SchemaId::fromString($xmlData['entity']['id']);

        $filePath = substr($file->getPathName(), 0, -strlen($file->getFilename()) - 1);
        $schemaPath = str_replace(':', '/', $schemaId->getCurie());

        // invalid schema file location
        if (strrpos($filePath, $schemaPath) === false) {
            throw new \RuntimeException(sprintf(
                'Invalid schema xml file "%s" location. Expected location "%s".',
                $filePath,
                $schemaPath
            ));
        }

        // duplicate schema
        if (array_key_exists($schemaId->toString(), $schemas)) {
            throw new \RuntimeException(sprintf(
                'Duplicate schema "%s" in file "%s".',
                $schemaId->toString(),
                $file
            ));
        }

        $schemas[$schemaId->toString()] = $xmlData['entity'];
    }

    /**
     * Parse enums and add to SchemaStore enums.
     *
     * @param array $enums
     *
     * @throw \RuntimeException
     */
    private function loadParseEnums($enums)
    {
        $parser = new EnumParser();

        foreach ($enums as $enum) {
            if (is_array($enum)) {
                if (!$enum = $parser->create($enum)) {
                    continue;
                }
            }

            SchemaStore::addEnum($enum->getId(), $enum);
        }
    }

    /**
     * Parse schemas and add to SchemaStore schemas.
     *
     * @param array $schemas
     *
     * @throw \RuntimeException
     */
    private function loadParseSchemas($schemas)
    {
        $parser = new SchemaParser();
        $validator = new SchemaValidator();

        $currentSchemaId = null;
        $exceptionSchemaId = [];

        while (count($schemas) > 0) {
            if (!$currentSchemaId) {
                $currentSchemaId = key($schemas);
            }

            $schema = $schemas[$currentSchemaId];

            if (is_array($schema)) {
                try {
                    $schema = $parser->create($schema);
                } catch (MissingSchema $e) {
                    $keys = preg_grep(sprintf('/^pbj:%s*/', str_replace(':v', ':', $e->getMessage())), array_keys($schemas));

                    if (count($keys) === 0) {
                        throw new \RuntimeException(sprintf('Schema with id "%s" is invalid.', $e->getMessage()));
                    }

                    $currentSchemaId = end($keys);

                    if (in_array($currentSchemaId, $exceptionSchemaId)) {
                        throw new \RuntimeException(sprintf('Recursively requesting schema with id "%s".', $currentSchemaId));
                    }

                    $exceptionSchemaId[] = $currentSchemaId;

                    continue;
                }
            }

            SchemaStore::addSchema($schema->getId(), $schema);

            $validator->validate($schema);

            unset($schemas[$currentSchemaId]);

            if (isset($exceptionSchemaId[$currentSchemaId])) {
                unset($exceptionSchemaId[$currentSchemaId]);
            }

            $currentSchemaId = null;
        }

        foreach (SchemaStore::getSchemasByCurieMajor() as $schema) {
            $schema->setIsLatestVersion(true);
        }
    }

    /**
     * Generates and writes files for each schema.
     *
     * @param string      $language
     * @param string      $namespace
     * @param string|null $output
     *
     * @return \Gdbots\Pbjc\Generator\Generator
     *
     * @throw \InvalidArgumentException
     */
    public function run($language, $namespace, $output = null)
    {
        if (!in_array($language, ['php', 'json'])) {
            throw new \InvalidArgumentException(sprintf(
                'The language "%s" is not supported. Only support "php" or "json".',
                $language
            ));
        }

        if (!preg_match('/^([a-z0-9-]+):([a-z0-9\.-]+)$/', $namespace)) {
            throw new \InvalidArgumentException(sprintf(
                'The namespace "%s" must follow "vendor:package" format.',
                $namespace
            ));
        }

        $class = sprintf('\Gdbots\Pbjc\Generator\%sGenerator', ucfirst($language));
        $generator = new $class($output);

        foreach (SchemaStore::getEnums() as $enum) {
            if ($namespace !== $enum->getId()->getNamespace()) {
                continue;
            }

            $generator->generateEnum($enum);
        }

        foreach (SchemaStore::getSchemas() as $schema) {
            if ($namespace !== $schema->getId()->getNamespace()) {
                continue;
            }

            $generator->generateSchema($schema);
        }

        $generator->generateMessageResolver(SchemaStore::getSchemasByNamespace($namespace));

        return $generator;
    }
}
