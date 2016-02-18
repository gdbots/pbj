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
    protected function loadSchemas()
    {
        // load all schema and store XML data
        foreach (SchemaStore::getDirs() as $dir) {
            $files = Finder::create()->files()->in($dir)->name('*.xml');

            foreach ($files as $key => $file) {
                // invalid schema
                if (!$xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../schema.xsd')) {
                    continue;
                }

                // bad \DOMDocument
                if (!$xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
                    continue;
                }

                $filePath = substr($file->getPathName(), 0, -strlen($file->getFilename()) - 1);

                // invalid scherma id
                if (!$this->validateXmlSchemaId($xmlData['entity']['id'], $filePath)) {
                    continue;
                }

                // ignore duplicates
                if (SchemaStore::getSchemaById($xmlData['entity']['id'], true)) {
                    continue;
                }

                SchemaStore::addSchema($xmlData['entity']['id'], $xmlData['entity'], true);
            }
        }

        $validator = new SchemaValidator();

        foreach (SchemaStore::getSchemas() as $schema) {
            if (is_array($schema)) {
                $schema = SchemaParser::create($schema);
            }

            $validator->validate($schema);

            SchemaStore::addSchema($schema->toString(), $schema, true);
        }

        foreach (SchemaStore::getSchemasByCurieMajor() as $schema) {
            $schema->setIsLatestVersion(true);
        }
    }

    /**
     * Validate the the dir sctructure match the schema id.
     *
     * @param array $schemaId
     * @param array $dir
     *
     * @return bool
     */
    protected function validateXmlSchemaId($schemaId, $dir)
    {
        $schemaId = SchemaId::fromString($schemaId);

        $schemaPath = sprintf(
            '%s/%s/%s',
            $schemaId->getVendor(),
            $schemaId->getPackage(),
            $schemaId->getCategory()
        );

        return (bool) strrpos($dir, $schemaPath);
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

        $class = sprintf('\Gdbots\Pbjc\Generator\%sGenerator', ucfirst($language));
        $generator = new $class();

        $generator->setOutput($output);

        foreach (SchemaStore::getSchemas() as &$schema) {
            if ($namespace !== sprintf(
                    '%s:%s',
                    $schema->getId()->getVendor(),
                    $schema->getId()->getPackage()
                )
                || $schema->getLanguageKey($generator->getLanguage(), 'isCompiled')
            ) {
                continue;
            }

            $generator->setSchema($schema);
            $generator->generate();

            if (count($schema->getEnums())) {
                $generator->generateEnums();
            }

            $schema->setLanguageKey($generator->getLanguage(), 'isCompiled', true);
        }

        return $generator;
    }
}
