<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\InvalidLanguage;
use Gdbots\Pbjc\Util\XmlUtils;
use Symfony\Component\Finder\Finder;

class Compiler
{
    /** @var array */
    const LANGUAGES = ['php'];

    /** @var string */
    protected $language;

    /** @var string */
    protected $output;

    /**
     * @param string $language
     * @param string $output
     *
     * @throw InvalidLanguage
     */
    public function __construct($language, $output = null)
    {
        $this->language = strtolower($language);
        $this->output = $output;

        if (!in_array($this->language, self::LANGUAGES)) {
            throw new InvalidLanguage(sprintf('Compile does not support "%s" language.', $this->language));
        }

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

            foreach ($files as $file) {
                if ($xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../schema.xsd')) {
                    if ($xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
                        SchemaStore::addSchema($xmlData['entity']['id'], $xmlData['entity'], true);
                    }
                }
            }
        }

        // resolve and mark schemas as latest
        $schemaLatestVersion = [];

        foreach (SchemaStore::getSortedSchemas() as $schema) {
            if (is_array($schema)) {
                $schema = $this->createSchema($schema);
            }

            if (!isset($schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()])) {
                $schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()] = $schema;
            }
            if ($schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()]
                ->getId()->getVersion() < $schema->getId()->getVersion()
            ) {
                $schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()] = $schema;
            }

            // update
            SchemaStore::addSchema($schema->__toString(), $schema, true);
        }

        foreach ($schemaLatestVersion as &$schema) {
            $schema->setIsLatestVersion(true);
        }
    }

    /**
     * Converts XML data into Schema instance.
     *
     * @param array $xmlData
     *
     * @return Schema
     */
    protected function createSchema(array $xmlData)
    {
        $fields    = [];
        $mixins    = [];
        $languages = [];

        if (isset($xmlData['fields']['field'])) {
            foreach ($xmlData['fields']['field'] as $field) {
                if (isset($field['type'])) {
                    if (isset($field['any_of'])) {
                        $anyOfClassNames = [];

                        foreach ($field['any_of'] as $schemaId) {
                            $schema = SchemaStore::getSchemaByCurie($schemaId);
                            if (is_array($schema)) {
                                $schema = $this->createSchema($schema);
                            }
                            if ($namespace = $schema->getLanguageOption('php', 'namespace')) {
                                $anyOfClassNames[] = $namespace;
                            }
                        }

                        $field['any_of'] = $anyOfClassNames;
                    }

                    $fields[] = Field::fromArray($field['name'], $field);
                }
            }
        }

        if (isset($xmlData['mixins']['id'])) {
            $mixins = [];

            foreach ((array) $xmlData['mixins']['id'] as $schemaId) {
                $schema = SchemaStore::getSchemaByCurieWithMajorRev($schemaId);
                if (is_array($schema)) {
                    $schema = $this->createSchema($schema);
                }

                $mixins[] = $schema;
            }
        }

        if (isset($xmlData['php_options'])) {
            $languages['php'] = $xmlData['php_options'];
        }

        $schema = new Schema($xmlData['id'], $fields, $mixins, $languages);

        if (isset($xmlData['mixin']) && $xmlData['mixin']) {
            $schema->setIsMixin(true);
        }

        return $schema;
    }

    /**
     * Generates and writes files for each schema.
     *
     * @return this
     */
    public function generate()
    {
        foreach (SchemaStore::getSortedSchemas() as &$schema) {
            if (!$schema->isDependent() && !$schema->getOption('isCompiled')) {
                $generator = new Generator($schema, $this->language);
                $generator->generate($this->output, !$this->output);

                $schema->setOption('isCompiled', true);
            }
        }
    }
}
