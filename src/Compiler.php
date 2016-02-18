<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Util\XmlUtils;
use Symfony\Component\Finder\Finder;

final class Compiler
{
    /**
     * Construct.
     */
    public function __construct()
    {
        $this->loadSchemas();
    }

    /**
     * Reads all schemas from all stored directories.
     *
     * @return this
     *
     * @throws \InvalidArgumentException
     */
    private function loadSchemas()
    {
        // load all schema and store XML data
        foreach (SchemaStore::getDirs() as $dir) {
            $files = Finder::create()->files()->in($dir)->name('*.xml');

            foreach ($files as $key => $file) {
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
                $schemaPath = sprintf(
                    '%s/%s/%s',
                    $schemaId->getVendor(),
                    $schemaId->getPackage(),
                    $schemaId->getCategory()
                );

                // invalid schema id
                if (strrpos($filePath, $schemaPath) === false) {
                    throw new \RuntimeException(sprintf(
                        'Invalid schema xml file "%s" location. Expected location "%s".',
                        $filePath,
                        $schemaPath
                    ));
                }

                // ignore duplicates
                if (SchemaStore::getSchemaById($schemaId, true)) {
                    throw new \RuntimeException(sprintf(
                        'Duplicate schema "%s" in file "%s".',
                        $schemaId->toString(),
                        $file
                    ));
                }

                SchemaStore::addSchema($schemaId, $xmlData['entity'], true);
            }
        }

        $parser = new SchemaParser();
        $validator = new SchemaValidator();

        foreach (SchemaStore::getSchemas() as $schema) {
            if (is_array($schema)) {
                $schema = $parser->create($schema);
            }

            $validator->validate($schema);

            SchemaStore::addSchema($schema->getId(), $schema, true);
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
        $generator = new $class();

        $generator->setOutput($output);

        if (!$output) {
            $generator->disableOutput();
        }

        foreach (SchemaStore::getSchemas() as &$schema) {
            if ($namespace !== $schema->getId()->getNamespace()
                || $schema->getLanguageKey($generator->getLanguage(), 'isCompiled')
            ) {
                continue;
            }

            $generator->setSchema($schema);
            $generator->generate();

            $schema->setLanguageKey($generator->getLanguage(), 'isCompiled', true);
        }

        return $generator;
    }
}
