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
                        $xmlData['entity']['is_dependent'] = $isDependent;

                        SchemaStore::addSchema($xmlData['entity']['id'], $xmlData['entity'], true);
                    }
                }
            }
        }

        // resolve and mark schemas as latest
        $schemaLatestVersion = [];

        foreach (SchemaStore::getSchemas() as $schema) {
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
     *
     * @throw \RuntimeException
     */
    protected function createSchema(array $xmlData)
    {
        $schemaId = SchemaId::fromString($xmlData['id']);

        $fields    = [];
        $mixins    = [];
        $languages = [];
        $options   = [];

        foreach (self::LANGUAGES as $language) {
            $languages[$language] = [];

            $attribute = sprintf('%s_options', $language);
            if (isset($xmlData[$attribute])) {
                $languages[$language] = $xmlData[$attribute];
            }
        }

        if (isset($xmlData['enums']['enum'])) {
            $options['enums'] = [];

            // handle single enum
            if (isset($xmlData['enums']['enum']['name'])) {
                $xmlData['enums']['enum'] = [$xmlData['enums']['enum']];
            }

            foreach ($xmlData['enums']['enum'] as $enum) {
                $options['enums'][$enum['name']] = [];

                // handle single option
                if (isset($enum['option']['key'])) {
                    $enum['option'] = [$enum['option']];
                }

                foreach ($enum['option'] as $option) {
                    $options['enums'][$enum['name']][$option['key']] = $option['value'];
                }
            }

            foreach (self::LANGUAGES as $language) {
                $languages[$language]['enums'] = [];

                $attribute = sprintf('%s_options', $language);
                if (isset($xmlData['enums'][$attribute])) {
                    $languages[$language]['enums'] = $xmlData['enums'][$attribute];
                }
            }

            // php: use schema namespace as a default
            if (!isset($languages['php']['enums']['namespace']) || !$languages['php']['enums']['namespace']) {
                $languages['php']['enums'] = [
                    'namespace' => $languages['php']['namespace']
                ];
            }
        }

        if (isset($xmlData['fields']['field'])) {
            foreach ($xmlData['fields']['field'] as $field) {
                if (isset($field['type'])) {
                    if (isset($field['any_of'])) {
                        $anyOfClassNames = [];

                        foreach ($field['any_of'] as $curie) {
                            $schema = SchemaStore::getSchemaByCurie($curie, $schemaId);
                            if (is_array($schema)) {
                                $schema = $this->createSchema($schema);
                            }

                            // php only
                            if ($namespace = $schema->getLanguageOption('php', 'namespace')) {
                                $anyOfClassNames[] = $namespace;
                            }
                        }

                        $field['any_of'] = $anyOfClassNames;
                    }

                    if (isset($field['enum'])) {
                        $schema = SchemaStore::getSchemaByCurieWithMajorRev($field['enum']['provider'], $schemaId);
                        if (is_array($schema)) {
                            $schema = $this->createSchema($schema);
                        }

                        $enums = $schema->getOption('enums');

                        if ($enums && isset($enums[$field['enum']['name']])) {
                            $field['enums'] = $enums[$field['enum']['name']];

                            if (isset($options['enums'][$field['enum']['name']])) {
                                $diff = array_diff($field['enums'], $options['enums'][$field['enum']['name']]);
                                if (count($diff) > 0) {
                                    throw new \RuntimeException(sprintf('No Enum keys ["%s"] can be removed from the same schema version.', implode('", "', array_keys($diff))));
                                }

                                $field['enums'] = $options['enums'][$field['enum']['name']];

                                // inherit the same options
                                foreach (self::LANGUAGES as $language) {
                                    if ($phpEnums = $schema->getLanguageOption($language, 'enums')) {
                                        $languages[$language]['enums'] = $phpEnums;
                                    }
                                }
                            }
                        }

                        // not needed
                        unset($field['enum']);
                    }

                    $fields[] = Field::fromArray($field['name'], $field);
                }
            }
        }

        if (isset($xmlData['mixins']['id'])) {
            $mixins = [];

            // handle single id
            if (is_string($xmlData['mixins']['id'])) {
                $xmlData['mixins']['id'] = [$xmlData['mixins']['id']];
            }

            foreach ($xmlData['mixins']['id'] as $curieWithMajorRev) {
                $schema = SchemaStore::getSchemaByCurieWithMajorRev($curieWithMajorRev, $schemaId);
                if (is_array($schema)) {
                    $schema = $this->createSchema($schema);
                }

                $mixins[] = $schema;
            }
        }

        $schema = new Schema($schemaId->__toString(), $fields, $mixins, $languages, $options);

        if (isset($xmlData['mixin']) && $xmlData['mixin']) {
            $schema->setIsMixin(true);
        }

        if (isset($xmlData['is_dependent']) && $xmlData['is_dependent']) {
            $schema->setIsDependent(true);
        }

        return $schema;
    }

    /**
     * Generates and writes files for each schema.
     *
     * @param bool $print
     *
     * @return this
     */
    public function generate($print = false)
    {
        foreach (SchemaStore::getSchemas() as &$schema) {
            if (!$schema->isDependent() && !$schema->getOption('isCompiled')) {
                $generatorClassName = sprintf('\Gdbots\Pbjc\Generator\%sGenerator', ucfirst($this->language));
                $generator = new $generatorClassName($schema);
                $generator->generate($this->output, $print);

                $schema->setOption('isCompiled', true);
            }
        }
    }
}
