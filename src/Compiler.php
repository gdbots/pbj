<?php

namespace Gdbots\Pbjc;

use Gdbots\Pbjc\Generator\Generator;
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
        foreach (SchemaStore::getDirs() as $dir => $isDependent) {
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

                $xmlData['entity']['is_dependent'] = $isDependent;

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

        foreach (SchemaStore::getSchemas() as $schema) {
            if (is_array($schema)) {
                $schema = SchemaParser::create($schema);
            }

            if (count($diff = SchemaValidator::validateMapping($schema)) > 0) {
                throw new \RuntimeException(sprintf(
                    'Schema ["%s"] is invalid. Schema has changed dramatically from previous version: [%s]',
                    $schema->getId()->toString(),
                    json_encode($diff)
                ));
            }

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
     * @param Generator $generator
     */
    public function run(Generator $generator)
    {
        foreach (SchemaStore::getSchemas() as &$schema) {
            if (!$schema->isDependent() && !$schema->getOptionSubOption($generator->getLanguage(), 'isCompiled')) {
                $generator->setSchema($schema);
                $generator->generate();

                if ($schema->getOption('enums')) {
                    $generator->generateEnums();
                }

                $schema->setOptionSubOption($generator->getLanguage(), 'isCompiled', true);
            }
        }
    }
}
