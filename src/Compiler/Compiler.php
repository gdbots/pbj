<?php

namespace Gdbots\Pbjc\Compiler;

use Gdbots\Pbjc\SchemaId;
use Gdbots\Pbjc\SchemaStore;
use Gdbots\Pbjc\SchemaTool;
use Gdbots\Pbjc\Util\XmlUtils;
use Symfony\Component\Finder\Finder;

abstract class Compiler
{
    /** @var string */
    protected $language;

    /** @var string */
    protected $output;

    /**
     * @param string $output
     */
    public function __construct($output = null)
    {
        $this->output = $output;

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
                if (!$xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../../schema.xsd')) {
                    continue;
                }

                // bad \DOMDocument
                if (!$xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
                    continue;
                }

                $xmlData['entity']['is_dependent'] = $isDependent;

                $filePath = substr($file->getPathName(), 0, -strlen($file->getFilename())-1);

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

        $schemaTool = new SchemaTool();
        foreach (SchemaStore::getSchemas() as $schema) {
            if (is_array($schema)) {
                $schema = $schemaTool->createSchema($schema);
            }

            // update
            SchemaStore::addSchema($schema->__toString(), $schema, true);
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
     * @return \Gdbots\Pbjc\Generator\Generator
     */
    public function generate()
    {
        $generator = $this->createGenerator();

        if ($this->output) {
            $generator->setOutput($this->output);
        } else {
            $generator->disableOutput();
        }

        foreach (SchemaStore::getSchemas() as &$schema) {
            if (!$schema->isDependent() && !$schema->getOptionSubOption($this->language, 'isCompiled')) {
                $generator->setSchema($schema);
                $generator->generate();

                if ($schema->getOption('enums')) {
                    $generator->generateEnums();
                }

                $schema->setOptionSubOption($this->language, 'isCompiled', true);
            }
        }

        return $generator;
    }

    /**
     * Returns a Generator instance.
     *
     * @return \Gdbots\Pbjc\Generator\Generator
     */
    abstract public function createGenerator();
}
