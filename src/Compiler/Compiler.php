<?php

namespace Gdbots\Pbjc\Compiler;

use Gdbots\Common\Util\StringUtils;
use Gdbots\Pbjc\Exception\InvalidLanguage;
use Gdbots\Pbjc\Enum;
use Gdbots\Pbjc\Field;
use Gdbots\Pbjc\Schema;
use Gdbots\Pbjc\SchemaId;
use Gdbots\Pbjc\SchemaStore;
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
                if ($xmlDomDocument = XmlUtils::loadFile($file, __DIR__.'/../../schema.xsd')) {
                    if ($xmlData = XmlUtils::convertDomElementToArray($xmlDomDocument->firstChild)) {
                        $xmlData['entity']['is_dependent'] = $isDependent;

                        $filePath = substr($file->getPathName(), 0, -strlen($file->getFilename())-1);

                        if ($this->validateXmlSchemaId($xmlData['entity']['id'], $filePath)) {
                            try {
                                if ($schema = SchemaStore::getSchemaById($xmlData['entity']['id'])) {
                                    if ($schema instanceof Schema) {
                                        if (!$schema->getOption($this->language)) {
                                            $schema->setOption(
                                                $this->language,
                                                $this->processXmlLanguageOptions($xmlData)
                                            );

                                            continue;
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                // the schema with id X is invalid - no added yet
                            }

                            SchemaStore::addSchema($xmlData['entity']['id'], $xmlData['entity'], true);
                        }
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
        $schema   = new Schema($schemaId->__toString());

        if (isset($xmlData['mixin']) && $xmlData['mixin']) {
            $schema->setIsMixin(true);
        }

        if (isset($xmlData['is_dependent']) && $xmlData['is_dependent']) {
            $schema->setIsDependent(true);
        }

        // default language options
        $schema->setOption($this->language, $this->processXmlLanguageOptions($xmlData));

        // assign enums
        if (isset($xmlData['enums']['enum'])) {
            $this->processXmlEnums($schema, $xmlData['enums']['enum']);

            // add enums language options
            $langOptionsKey = sprintf('%s_options', $this->language);
            if (isset($xmlData['enums'][$langOptionsKey])) {
                $schema->setOptionSubOption($this->language, 'enums', $xmlData['enums'][$langOptionsKey]);
            }

            // inherit from previous version
            $prevSchemaVersion = SchemaStore::getSchemaByCurieWithMajorRev($schema->getId()->getCurieWithMajorRev(), $schema->getId());
            if ($prevSchemaVersion instanceof Schema &&
                $prevSchemaVersion->getId()->getVersion()->__toString() < $schema->getId()->getVersion()->__toString()
            ) {
                $schema->setOptionSubOption($this->language, 'enums', array_merge(
                    $schema->getOptionSubOption($this->language, 'enums', []),
                    $prevSchemaVersion->getOptionSubOption($this->language, 'enums', [])
                ));

                $enumNames = [];
                foreach ($schema->getOption('enums', []) as $enum) {
                    $enumNames[] = $enum->getName();
                }

                foreach ($prevSchemaVersion->getOption('enums', []) as $enum) {
                    if (!in_array($enum->getName(), $enumNames)) {
                        $schema->setOption('enums', array_merge(
                            $schema->getOption('enums', []),
                            [
                                $enum
                            ]
                        ));
                    }
                }
            }
        }

        if (isset($xmlData['fields']['field'])) {
            $this->processXmlFields($schema, $xmlData['fields']['field']);
        }

        if (isset($xmlData['mixins']['id'])) {
            $this->processXmlMixins($schema, $xmlData['mixins']['id']);
        }

        return $schema;
    }

    /**
     * @param array|string $data
     * @param string       $key
     *
     * @return array
     */
    protected function convertXmlDataToArray($data, $key = null)
    {
        if (!is_array($data) || ($key && isset($data[$key]))) {
            $data = [$data];
        }
        return $data;
    }

    /**
     * @param array $data
     *
     * @return array
     */
    protected function processXmlLanguageOptions(array $data)
    {
        $langOptionsKey = sprintf('%s_options', $this->language);
        if (isset($data[$langOptionsKey])) {
            return $data[$langOptionsKey];
        }

        return [];
    }

    /**
     * @param Schema $schema
     * @param array  $data
     */
    protected function processXmlEnums(Schema $schema, array $data)
    {
        $data = $this->convertXmlDataToArray($data, 'name');
        foreach ($data as $item) {
            // force default type to be "string"
            if (!isset($item['type'])) {
                $item['type'] = 'string';
            }

            $values = [];
            $keys = $this->convertXmlDataToArray($item['option'], 'key');
            foreach ($keys as $key) {
                $values[$key['key']] = $item['type'] == 'int'
                    ? intval($key['value'])
                    : (string) $key['value']
                ;
            }

            // validate from previous version
            $prevSchemaVersion = SchemaStore::getSchemaByCurieWithMajorRev($schema->getId()->getCurieWithMajorRev(), $schema->getId());
            if ($prevSchemaVersion instanceof Schema &&
                $prevSchemaVersion->getId()->getVersion()->__toString() < $schema->getId()->getVersion()->__toString()
            ) {
                $enums = $prevSchemaVersion->getOption('enums');
                foreach ($enums as $enum) {
                    if ($enum->getName() == $item['name']) {
                        $diff = array_diff($enum->getValues(), $values);
                        if (count($diff) > 0) {
                            throw new \RuntimeException(sprintf(
                                'No Enum keys ["%s"] can be removed from the same schema version.',
                                implode('", "', array_keys($diff))
                            ));
                        }

                        break;
                    }
                }
            }

            $enum = new Enum($item['name'], $values);

            $schema->setOption('enums', array_merge(
                $schema->getOption('enums', []),
                [
                    $enum
                ]
            ));
        }
    }

    /**
     * @param Schema $schema
     * @param array  $data
     */
    protected function processXmlFields(Schema $schema, $data)
    {
        $data = $this->convertXmlDataToArray($data);
        foreach ($data as $item) {
            // ignore if no type was assign
            if (!isset($item['type'])) {
                continue;
            }

            if (!isset($item['options'])) {
                $item['options'] = [];
            }

            if (isset($item['any_of']['id'])) {
                $anyOf = $this->convertXmlDataToArray($item['any_of']['id']);

                /** @var $item['any_of'] Schema[] */
                $item['any_of'] = [];

                foreach ($anyOf as $curie) {
                    // can't add yourself to anyof
                    if ($curie == $schema->getId()->getCurieWithMajorRev()) {
                        continue;
                    }

                    $anyOfSchema = SchemaStore::getSchemaByCurie($curie, $schema->getId());
                    if (is_array($anyOfSchema)) {
                        $anyOfSchema = $this->createSchema($anyOfSchema);
                    }

                    $item['any_of'][] = $anyOfSchema;
                }
            }
            if (isset($item['any_of']) && count($item['any_of']) === 0) {
                unset($item['any_of']);
            }

            if (isset($item['enum'])) {
                if ($item['enum']['provider'] == $schema->getId()->getCurieWithMajorRev()) {
                    $providerSchema = $schema;
                } else {
                    $providerSchema = SchemaStore::getSchemaByCurieWithMajorRev($item['enum']['provider'], $schema->getId());
                    if (is_array($providerSchema)) {
                        $providerSchema = $this->createSchema($providerSchema);
                    }

                    // use current schema if the same and previous version
                    if ($providerSchema->getId()->getCurieWithMajorRev() == $schema->getId()->getCurieWithMajorRev() &&
                        $providerSchema->getId()->getVersion()->__toString() < $schema->getId()->getVersion()->__toString()
                    ) {
                        $providerSchema = $schema;
                    }
                }

                /** @var $enums Enum[] */
                if ($enums = $providerSchema->getOption('enums')) {
                    foreach ($enums as $enum) {
                        if ($enum->getName() == $item['enum']['name']) {
                            $item['options']['enum'] = $enum;

                            break;
                        }
                    }
                }

                unset($item['enum']);
            }

            $schema->addField(new Field($item['name'], $item));
        }
    }

    /**
     * @param Schema       $schema
     * @param array|string $data
     */
    protected function processXmlMixins(Schema $schema, $data)
    {
        $data = $this->convertXmlDataToArray($data);
        foreach ($data as $curieWithMajorRev) {
            // can't add yourself to mixins
            if ($curieWithMajorRev == $schema->getId()->getCurieWithMajorRev()) {
                continue;
            }

            $mixinSchema = SchemaStore::getSchemaByCurieWithMajorRev($curieWithMajorRev, $schema->getId());
            if (is_array($mixinSchema)) {
                $mixinSchema = $this->createSchema($mixinSchema);
            }

            $schema->setOption('mixins', array_merge(
                $schema->getOption('mixins', []),
                [
                    $mixinSchema
                ]
            ));
        }
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
            if (!$schema->isDependent() && !$schema->getOptionSubOption($this->language, 'isCompiled')) {
                $generator = $this->createGenerator($schema);
                $generator->generate($this->output, $print);

                if ($schema->getOption('enums')) {
                    $generator->generateEnums($this->output, $print);
                }

                $schema->setOptionSubOption($this->language, 'isCompiled', true);
            }
        }
    }

    /**
     * Returns a Generator instance.
     *
     * @param Schema $schema
     *
     * @return \Gdbots\Pbjc\Generator\Generator
     */
    abstract public function createGenerator(Schema $schema);
}
