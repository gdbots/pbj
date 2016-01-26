<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\InvalidLanguage;
use Symfony\Component\Finder\Finder;

class Compiler
{
    /** @var array */
    const LANGUAGES = ['php', 'json'];

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
     */
    protected function loadSchemas()
    {
        foreach (SchemaStore::getDirs() as $dir) {
            $files = Finder::create()->files()->in($dir)->name('*.xml');

            foreach ($files as $file) {
                if ($xmlObject = $this->parseFile($file)) {
                    $schema = $this->createSchema($xmlObject);

                    SchemaStore::addSchema($schema->__toString(), $schema, true);
                }
            }
        }

        $schemaLatestVersion = [];

        foreach (SchemaStore::getSortedSchemas() as &$schema) {
            if (!isset($schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()])) {
                $schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()] = $schema;
            }

            if ($schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()]
                ->getId()->getVersion() < $schema->getId()->getVersion()) {

                $schemaLatestVersion[$schema->getId()->getCurieWithMajorRev()] = $schema;
            }
        }

        foreach ($schemaLatestVersion as &$schema) {
            $schema->setIsLatestVersion(true);
        }
    }

    /**
     * Parses an XML file.
     *
     * @param string $file Path to a file
     *
     * @return \SimpleXMLElement
     *
     * @throws \InvalidArgumentException When loading of XML file returns error
     */
    protected function parseFile($file)
    {
        try {
            return new \SimpleXMLElement(file_get_contents($file));
        } catch (\Exception $e) {
            $e->setParsedFile($file);

            throw new \InvalidArgumentException(sprintf('Unable to parse file "%s".', $file), $e->getCode(), $e);
        }
    }

    /**
     * Converts XML data into Schema instance.
     *
     * @param \SimpleXMLElement $xmlObject
     *
     * @return Schema
     */
    protected function createSchema(\SimpleXMLElement $xmlObject)
    {
        $fields    = [];
        $mixins    = [];
        $languages = [];

        $schemaXmlObject = $xmlObject->schema;
        $id = $schemaXmlObject->attributes()->id->__toString();
        $mixin = $schemaXmlObject->attributes()->mixin->__toString() === 'true';

        if (isset($schemaXmlObject->field)) {
            foreach ($schemaXmlObject->field as $field) {
                $attributes = [];

                foreach ($field->attributes() as $key => $value) {
                    $attributes[$key] = $value->__toString();
                }

                if (isset($field->php_options)) {
                    $phpOptions = $field->php_options;

                    foreach ($phpOptions->attributes() as $key => $value) {
                        $attributes['php_options'][$key] = $value->__toString();
                    }
                }

                if (isset($attributes['type'])) {
                    $fields[] = Field::fromArray($attributes['name'], $attributes);
                }
            }
        }

        if (isset($schemaXmlObject->mixins)) {
            foreach ($schemaXmlObject->mixins as $option) {
                $mixins[] = $option->option->attributes()->id->__toString();
            }
        }

        if (isset($schemaXmlObject->php_options)) {
            $phpOptions = $schemaXmlObject->php_options;

            foreach ($phpOptions->attributes() as $key => $value) {
                $languages['php'][$key] = $value->__toString();
            }
        }

        $schema = new Schema($id, $fields, $mixins, $languages);

        if ($mixin) {
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
            if (!$schema->getOption('isCompiled')) {
                $generator = new Generator($schema, $this->language);
                $generator->generate($this->output);

                $schema->setOption('isCompiled', true);
            }
        }
    }
}
