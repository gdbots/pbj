<?php

namespace Gdbots\Pbjc;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\InvalidLanguage;
use Gdbots\Pbjc\Util\XmlUtils;
use Symfony\Component\Finder\Finder;

class Compiler
{
    /** @var array */
    const LANGUAGES = ['php', 'json'];

    /** @var string */
    protected $output;

    /**
     * @param string $output
     *
     * @throw InvalidLanguage
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
            $selectLanguageOptionsKey = sprintf('%s_options', $language);

            $languages[$language] = [];
            if (isset($xmlData[$selectLanguageOptionsKey])) {
                $languages[$language] = $xmlData[$selectLanguageOptionsKey];
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

                if (!isset($enum['type'])) {
                    $enum['type'] = 'string';
                }

                // handle single option
                if (isset($enum['option']['key'])) {
                    $enum['option'] = [$enum['option']];
                }

                foreach ($enum['option'] as $option) {
                    $options['enums'][$enum['name']][$option['key']] = $enum['type'] == 'int' ? intval($option['value']) : (string) $option['value'];
                }
            }

            foreach (self::LANGUAGES as $language) {
                $selectLanguageOptionsKey = sprintf('%s_options', $language);

                $languages[$language]['enums'] = [];
                if (isset($xmlData['enums'][$selectLanguageOptionsKey])) {
                    $languages[$language]['enums'] = $xmlData['enums'][$selectLanguageOptionsKey];
                }

                // php: use schema namespace as a default
                if ($language == 'php' &&
                    (
                        !isset($languages['php']['enums']['namespace'])
                        || !$languages['php']['enums']['namespace']
                    )
                ) {
                    $languages['php']['enums'] = [
                        'namespace' => $languages['php']['namespace']
                    ];
                }
            }
        }

        if (isset($xmlData['fields']['field'])) {
            foreach ($xmlData['fields']['field'] as $field) {
                if (isset($field['type'])) {
                    if (isset($field['any_of']['id'])) {
                        // handle single class
                        if (!is_array($field['any_of']['id'])) {
                            $field['any_of']['id'] = [$field['any_of']['id']];
                        }

                        $anyOfClassNames = [];

                        foreach ($field['any_of']['id'] as $curie) {
                            $schema = SchemaStore::getSchemaByCurie($curie, $schemaId);
                            if (is_array($schema)) {
                                $anyOfClassNames[] = $this->createSchema($schema);
                            }
                        }

                        $field['any_of_class_names'] = $anyOfClassNames;

                        // not needed
                        unset($field['any_of']);
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
                            }
                        }

                        // inherit the same options
                        foreach (self::LANGUAGES as $language) {
                            if (!$enums = $schema->getLanguageOption($language, 'enums')) {
                                $enums = $languages[$language]['enums'];
                            }

                            $languages[$language]['enums'] = $enums;

                            // php only
                            if ($language == 'php') {
                                if (substr($enums['namespace'], 0, 1) == '\\') {
                                    $enums['namespace'] = substr($enums['namespace'], 1);
                                }

                                $field['php_options']['class_name'] =
                                    sprintf('%s\%s%sV%d',
                                        $enums['namespace'],
                                        $schema->getClassName(),
                                        StringUtils::toCamelFromSlug($field['enum']['name']),
                                        $schema->getId()->getVersion()->getMajor()
                                    )
                                ;
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
     * @param string $language
     * @param bool   $print
     *
     * @return this
     */
    public function generate($language, $print = false)
    {
        if (!in_array($language, self::LANGUAGES)) {
            throw new InvalidLanguage(sprintf('Compile does not support "%s" language.', $language));
        }

        foreach (SchemaStore::getSchemas() as &$schema) {
            $isCompiled = sprintf('is%sCompiled', ucfirst($language));

            if (!$schema->isDependent() && !$schema->getOption($isCompiled)) {
                $generatorClassName = sprintf('\Gdbots\Pbjc\Generator\%sGenerator', ucfirst($language));
                $generator = new $generatorClassName($schema);
                $generator->generate($this->output, $print);

                if ($schema->getOption($language, 'enums')) {
                    $generator->generateEnums($this->output, $print);
                }

                $schema->setOption($isCompiled, true);
            }
        }
    }
}
